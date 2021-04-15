<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Transfer;
use Increment\Marketplace\Models\TransferredProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class TransferController extends APIController
{
    public $transferredProductsClass = 'Increment\Marketplace\Http\TransferredProductController';
    public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
    public $productClass = 'Increment\Marketplace\Http\ProductController';
    public $productAttrClass = 'Increment\Marketplace\Http\ProductAttributeController';
    public $productTraceClass = 'Increment\Marketplace\Http\ProductTraceController';
    public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
    public $landBlockProductClass = 'App\Http\Controllers\LandBlockProductController';
    public $orderRequestClass = 'Increment\Marketplace\Http\OrderRequestController';
    public $dailyLoadingListClass = 'Increment\Marketplace\Http\DailyLoadingListController';
    public $batcProductClass = 'Increment\Marketplace\Paddock\Http\BatchProductController';
    function __construct(){
      $this->model = new Transfer();
      $this->localization();

      $this->notRequired = array(
        'order_request_id'
      );
    }

    public function create(Request $request){
      $data = $request->all();
      $data['code'] = $this->generateCode();
      $this->insertDB($data);
      return $this->response();
    }

    public function createDeliveries(Request $request){
      $data = $request->all();
      $data['code'] = $this->generateCode();
      $this->insertDB($data);

      if($this->response['data'] > 0){
        $products = $data['products'];

        foreach ($products as $key) {

          $existTrace = TransferredProduct::where('payload_value', '=', $key['product_trace'])->orderBy('created_at', 'desc')->limit(1)->get();

          if(sizeof($existTrace) > 0){
            TransferredProduct::where('id', '=', $existTrace[0]['id'])->update(
              array(
                'status' => 'inactive',
                'updated_at'  => Carbon::now()
              )
            );
          }
          $productTrace = app($this->productTraceClass)->getDetailsByParams('id', $key['product_trace'], ['id', 'code', 'product_attribute_id']);
          if($productTrace){
            $item = array(
              'transfer_id' => $this->response['data'],
              'payload'     => 'product_traces',
              'payload_value' => $key['product_trace'],
              'product_id'  => $key['product_id'],
              'merchant_id'  => $data['to'],
              'product_attribute_id' => $productTrace['product_attribute_id'],
              'status'      => 'active',
              'created_at'  => Carbon::now()
            );

            TransferredProduct::insert($item);
          }else{
            $this->response['data'] = null;
            $this->response['error'] = 'Invalid product trace.';
            return $this->response();
          }
        }

        app($this->orderRequestClass)->updateByParams($data['order_request_id'], array(
          'status'  => 'completed',
          'date_delivered'  => Carbon::now(),
          'updated_at'  => Carbon::now()
        ));
        
        app($this->dailyLoadingListClass)->updateByParams('order_request_id', $data['order_request_id'], array(
          'status'  => 'completed',
          'updated_at'  => Carbon::now()
        ));
      }

      return $this->response();
    }
    
    public function generateCode(){
      $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32);
      $codeExist = Transfer::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
      $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
      $size = null;
      $result = array();
      if($data['column'] == 'created_at'){
        $sort = array(
          $data['sort']['column'] => $data['sort']['value']
        );
        $parameter = array(
          'condition' => array(array(
              'column'  => $data['column'],
              'value'  => $data['value'],
              'clause'  => 'like'
            ), array(
              'column' => $data['filter_value'],
              'value'  => $data['merchant_id'],
              'clause' => '=' 
            )
          ),
          'sort'    => $sort,
          'limit'   => $data['limit'],
          'offset'  => $data['offset']
        );
        $this->model = new Transfer();
        $this->retrieveDB($parameter);
        $size = Transfer::where($data['column'], 'like', $data['value'])->where($data['filter_value'], '=', $data['merchant_id'])->count();
        $result = $this->response['data'];
      }else if($data['column'] == 'username'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('accounts as T2', 'T2.id', '=', 'T1.from')
          ->where('T2.username', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }else if($data['column'] == 'name'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('merchants as T2', 'T2.id', '=', 'T1.to')
          ->where('T2.name', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['transferred_products'] = app($this->transferredProductsClass)->getSizeNoDate('transfer_id', $result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
          $this->response['data'][$i]['to_details'] = app($this->merchantClass)->getByParamsConsignments('id', $result[$i]['to']);
          $this->response['data'][$i]['from_details'] = app($this->merchantClass)->getByParamsConsignments('id', $result[$i]['from']);
          $this->response['data'][$i]['order_requests'] = app($this->orderRequestClass)->getColumnByParams('id', $result[$i]['order_request_id'], ['order_number', 'id']);
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetailsTransfer($result[$i]['account_id']);
          $i++;
        }
      }

      $this->response['size'] = $size;

      return $this->response();
    }

    public function retrieveAllowedOnly(Request $request){
      $data = $request->all();
      $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
      $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
      $size = null;
      $result = array();
      if($data['column'] == 'created_at'){
        $sort = array(
          $data['sort']['column'] => $data['sort']['value']
        );
        $parameter = array(
          'condition' => array(array(
              'column'  => $data['column'],
              'value'  => $data['value'],
              'clause'  => 'like'
            ), array(
              'column' => $data['filter_value'],
              'value'  => $data['merchant_id'],
              'clause' => '=' 
            )
          ),
          'sort'    => $sort,
          'limit'   => $data['limit'],
          'offset'  => $data['offset']
        );
        $this->model = new Transfer();
        $this->retrieveDB($parameter);
        $size = Transfer::where($data['column'], 'like', $data['value'])->where($data['filter_value'], '=', $data['merchant_id'])->count();
        $result = $this->response['data'];
      }else if($data['column'] == 'username'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('accounts as T2', 'T2.id', '=', 'T1.from')
          ->where('T2.username', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }else if($data['column'] == 'name'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('merchants as T2', 'T2.id', '=', 'T1.to')
          ->where('T2.name', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }
      if(sizeof($result) > 0){
        $i = 0;
        $array = array();
        foreach ($result as $key) {
          $item = array(
            'code'  =>  $key['code'],
            'name'  =>  $this->retrieveName($key['account_id']),
            'number_of_items'   =>  app($this->transferredProductsClass)->getSizeNoDate('transfer_id', $key['id']),
            'trasferred_on' => Carbon::createFromFormat('Y-m-d H:i:s', $key['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A'),
            'to'    => app($this->merchantClass)->getColumnValueByParams('id', $result[$i]['to'], 'name'),
            'from'  => app($this->merchantClass)->getColumnValueByParams('id', $result[$i]['from'], 'name'),
            'order_requests' => app($this->orderRequestClass)->getColumnByParams('id', $result[$i]['order_request_id'], ['order_number', 'id'])
          );
          $array[] = $item;
          $i++;
        }
        $this->response['data'] =  $array;
      }
      $this->response['size'] = $size;
      return $this->response();
    }

    public function retrieveConsignmentsImprove(Request $request){
      // get all products ? as this is based on consigments ?
      // limit
      // offset
      // get inventory
      // get total inventory both bundled and traces
    }

    public function retrieveConsignments(Request $request){
      $data = $request->all();
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T1.to', '=', $data['merchant_id'])
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->get(['T2.product_id', 'T2.created_at', 'T2.payload_value']);
      $result = $result->groupBy('product_id');
      // $this->response['data'] = $result;
      // return $this->response();
      $i = 0;
      $testArray = array();
      foreach ($result as $key => $value) {
        $size = 0;
        $bundledQty = 0;
        $productTrace = null;
        $test = null;
        $consumedValue = null;
        foreach ($value as $keyInner) {
          $productTrace = $keyInner->payload_value;
          $tSize = app($this->transferredProductsClass)->getSizeLimit('payload_value', $keyInner->payload_value, $keyInner->created_at);

          $bundled = app($this->bundledProductController)->getByParamsNoDetailsWithLimit('product_trace', $keyInner->payload_value, 1);
          $trace = app($this->productTraceClass)->getByParamsByFlag('id', $productTrace);
          if($data['type'] != 'USER'){
            $size += ($tSize > 0) ? 0 : 1;
          }else if($tSize == 0 && $bundled == null && $trace == true){
            $comsumed = 0;
            $comsumed = app($this->landBlockProductClass)->getTotalConsumedByTrace($data['merchant_id'], $productTrace, $keyInner->product_id);
            $size += (1 - $comsumed);
            $consumedValue = $size;
          }
          // if($tSize == 0 && $bundled == null && $trace == true && $data['type'] == 'USER'){
          //   // only to end user
          //   // should add user type on the parameter
          //   $comsumed = 0;
          //   $comsumed = app($this->landBlockProductClass)->getTotalConsumedByTrace($data['merchant_id'], $productTrace, $keyInner->product_id);
          //   $size += (1 - $comsumed);
          //   $consumedValue = $size;
          // }

          if($bundled != null){
            $bundledTransferred = TransferredProduct::where('payload_value', '=', $bundled['bundled_trace'])->where('deleted_at', '=', null)->limit(1)->count();
            if($bundledTransferred == 0){
              $bundledQty++;
            }
          }
          // $testArray[] = array(
          //   'product_id' => $keyInner->product_id,
          //   'trace' =>  $keyInner->payload_value,
          //   'test'  => $test,
          //   'consumed' => $consumedValue
          // );
        }
        if($size > 0){
          $product =  app($this->productClass)->getProductByParamsConsignments('id', $key);
          $product['qty'] = $size;
          $product['qty_in_bundled'] = $bundledQty;
          $product['productTrace'] = $productTrace;
          $product['test'] = $test;
          $this->response['data'][] = $product;
          $this->manageQtyWithBundled($product, $productTrace);
          $i++;
        }
      }
      // $this->response['data'] = $testArray;
      return $this->response();
    }

  public function retrieveByConsignmentsPagination(Request $request){
    $data = $request->all();
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
    $result = DB::table('transferred_products as T1')
    ->join('products as T2', 'T2.id', '=', 'T1.product_id')
    ->where('T1.merchant_id', '=', $data['merchant_id'])
    ->where('T1.status', '=', 'active')
    ->orderBy('T2.title', 'asc')
    ->get(['T1.*', 'T2.title']);
    $result = $result->groupBy('product_id');
    $size = $result->count();
    $testArray = array();
    if(sizeof($result) > 0){  
      foreach($result as $key => $value){
        // account_id: "7"
        // code: "V6Q1G3IFL47HCOY5R0DEJAMXTWKZUP92"
        // created_at: "2020-03-25 07:59:24"
        // created_at_human: "March 25, 2020 15:59 PM"
        // deleted_at: null
        // description: "2 UNITS - WETTER"
        // featured: null
        // id: 8
        // inventories: null
        // merchant_id: "2"
        // price_settings: "fixed"
        // product_traces: null
        // qty: 0
        // qty_in_bundled: 0
        // rf: null
        // sku: null
        // status: "pending"
        // tag_array: null
        // tags: null
        // title: "2 x WETTER"
        // type: "bundled"
        // updated_at: "2020-03-25 07:59:24"
        $product = app($this->productClass)->getByParams('id', $key);
        $item = array(
          'title'     => $product ? $product['title'] : null,
          'id'        => $key,
          'merchant'  => array(
            'name'    => $product ? app($this->merchantClass)->getColumnValueByParams('id', $product['merchant_id'], 'name') : null
          ),
          'qty'     => sizeof($value),
          'qty_in_bundled' => $this->getBundledProducts($data['merchant_id'], $key),
          'type'    => $product ? $product['type'] : null
        );
        $testArray[] = $item;
      }
    }
    $this->response['data'] = $testArray;
    $this->response['size'] = $size;
    return $this->response();
  }

  public function retrieveProductsFirstLevelEndUser(Request $request){
    $data = $request->all();
    $con = $data['condition'];
    $result = null;
    $size = null;
    $productType = $data['productType'];
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;

    $whereArray = array(
      array($con['column'], 'like', $con['value']),
      array('T1.deleted_at', '=', null),
      array('T2.status', '=', 'active'),
      array('T2.merchant_id', '=', $data['merchant_id'])
    );

    if(isset($data['tags'])){
      if($data['tags'] == 'other'){
        array_push($whereArray, 
            array('T1.tags', 'not like', 'herbicide'),
            array('T1.tags', 'not like', 'fungicide'),
            array('T1.tags', 'not like', 'insecticide')
        );
        $result = DB::table('products as T1')
            ->leftJoin('transferred_products as T2', 'T2.product_id', '=', 'T1.id')
            ->where($whereArray)
            ->groupBy('T2.product_attribute_id')
            ->skip($data['offset'])->take($data['limit'])
            ->orderBy($con['column'], $data['sort'][$con['column']])
            ->select('T1.id', 'T1.code', 'T1.title',  'T2.product_attribute_id', 'T1.tags', 'T1.merchant_id as from', 'T2.merchant_id as to', 'T1.type', 'T1.tags', 'T1.description')
            ->get();
      }else{
        array_push($whereArray, array('T1.tags', 'like', $data['tags']));
        $result = DB::table('products as T1')
          ->leftJoin('transferred_products as T2', 'T2.product_id', '=', 'T1.id')
          ->where($whereArray)
          ->groupBy('T2.product_attribute_id')
          ->skip($data['offset'])->take($data['limit'])
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->select('T1.id', 'T1.code', 'T1.title',  'T2.product_attribute_id', 'T1.tags', 'T1.merchant_id as from', 'T2.merchant_id as to', 'T1.type', 'T1.tags', 'T1.description')
          ->get();
      }
    }else{
      $result = DB::table('products as T1')
        ->leftJoin('transferred_products as T2', 'T2.product_id', '=', 'T1.id')
        ->where($whereArray)
        ->groupBy('T2.product_attribute_id')
        ->skip($data['offset'])->take($data['limit'])
        ->orderBy($con['column'], $data['sort'][$con['column']])
        ->select('T1.id', 'T1.code', 'T1.title',  'T2.product_attribute_id', 'T1.tags', 'T1.merchant_id as from', 'T2.merchant_id as to', 'T1.type', 'T1.tags', 'T1.description')
        ->get();
    }

    if(sizeof($result) > 0){
      $this->manageResultEndUserFirstLevel($result, $data);
      $this->response['data'] = array_values($this->response['data']);
      return $this->response();
    }else{
      $this->response['data'] = [];
      return $this->response();
    }
  }

  public function manageResultEndUserFirstLevel($products, $data){
    $i = 0;
    foreach ($products as $key) {
      $productId = $products[$i]->id;
      $productQty = app($this->transferredProductsClass)->getTransferredProduct($productId, $data['merchant_id'], $products[$i]->product_attribute_id);
      // $consumed = app('Increment\Marketplace\Paddock\Http\BatchProductController')->getTotalAppliedRateBySpecifiedParams($productId, $data['merchant_id']);
      // $qty = app($this->productTraceClass)->getBalanceQtyOnManufacturer('product_id', $products[$i]->product_id);
      $attributes = app($this->productAttrClass)->getByParams('id', $products[$i]->product_attribute_id);
      if($productQty->qty > 0){
        $merchantFrom = app($this->merchantClass)->getColumnValueByParams('id', $products[$i]->from, 'name');
        $merchant =  app($this->merchantClass)->getColumnValueByParams('id', $products[$i]->to, 'name');

        $qty = 0;
        $j = 0;
        foreach ($attributes as $attributeKey) {
          $productAttributeId = $attributes[$j]['id'];
          $volume = floatval($attributes[$j]['payload_value']);
          $totalProductTraces = app($this->transferredProductsClass)->getActiveProductQtyInAttribute($productId, $productAttributeId, $data['merchant_id']);
          $totalConsumed = app('Increment\Marketplace\Paddock\Http\BatchProductController')->getTotalAppliedRateByParamsByAttribute($productId, $productAttributeId, $data['merchant_id']);
          $totalConsumedInTraces = floatval($totalConsumed / $volume);
          $qty += $totalProductTraces - $totalConsumedInTraces;
          $j++;
        }
        $string = $attributes[0]['payload'];
        $temps = explode(' ', $string);
        $final = array_pop($temps);
        $this->response['data'][$i]['volume'] = $attributes[0]['payload_value'].''.$final;
        $this->response['data'][$i]['merchant'] = array('name' => $merchant);
        $this->response['data'][$i]['type'] = $products[$i]->type;
        $this->response['data'][$i]['title'] = $products[$i]->title;
        $this->response['data'][$i]['tags'] = $products[$i]->tags;
        $this->response['data'][$i]['code'] = $products[$i]->code;
        $this->response['data'][$i]['product_id'] = $products[$i]->id;
        $this->response['data'][$i]['description'] = $products[$i]->description;
        $this->response['data'][$i]['product_attribute_id'] = $products[$i]->product_attribute_id;
        $this->response['data'][$i]['merchant_from'] = $merchantFrom;
        $this->response['data'][$i]['manufacturing_date'] = $productQty != null ? $productQty->manufacturing_date : null;
        $this->response['data'][$i]['qty'] = number_format($qty, 2);
        $this->response['data'][$i]['qty_in_bundled'] = 0; // $qty['qty_in_bundled'];
        $this->response['data'][$i]['details'] = $this->retrieveProductDetailsByParams('id', $productId);
        
      }
      $i++;
    }
  }

  public function retrieveProductsFirstLevelEndUserOld(Request $request){
    $data = $request->all();
    $con = $data['condition'];
    $result = null;
    $size = null;
    $productType = $data['productType'];
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
    if($productType == 'all'){
      if(isset($data['tags'])){
        if($data['tags'] == 'other'){
          $products = DB::table('products as T1')
                ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
                ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
                ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
                ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
                ->where('T5.to', '=', $data['merchant_id'])
                ->where($con['column'], 'like', $con['value'])
                ->where(function($query){
                  $query->where('T1.tags', 'not like', 'herbicide')
                        ->Where('T1.tags', 'not like', 'fungicide')
                        ->Where('T1.tags', 'not like', 'insecticide');
                })
                ->where('T2.deleted_at', '=', null)
                ->groupBy('T1.id')
                ->select('*', 'T1.code as product_code', 'T2.payload as unit', 'T2.payload_value as unit_value')
                ->skip($data['offset'])->take($data['limit'])
                ->orderBy($con['column'], $data['sort'][$con['column']])
                ->get();
          
          $size = DB::table('products as T1')
              ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
              ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
              ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
              ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
              ->where($con['column'], 'like', $con['value'])
              ->where('T5.to', '=', $data['merchant_id'])
              ->where(function($query){
                $query->where('T1.tags', 'not like', 'herbicide')
                      ->Where('T1.tags', 'not like', 'fungicide')
                      ->Where('T1.tags', 'not like', 'insecticide');
              })
              ->where('T2.deleted_at', '=', null)
              ->groupBy('T1.id')
              ->orderBy($con['column'], $data['sort'][$con['column']])
              ->get();
        }else{
          $products = DB::table('products as T1')
                ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
                ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
                ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
                ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
                ->where($con['column'], 'like', $con['value'])
                ->where('T5.to', '=', $data['merchant_id'])
                ->where('T1.tags', 'like', $data['tags'])
                ->where('T2.deleted_at', '=', null)
                ->groupBy('T1.id')
                ->select('*', 'T1.code as product_code', 'T2.payload as unit', 'T2.payload_value as unit_value')
                ->skip($data['offset'])->take($data['limit'])
                ->orderBy($con['column'], $data['sort'][$con['column']])
                ->get();

          $size = DB::table('products as T1')
                ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
                ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
                ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
                ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
                ->where($con['column'], 'like', $con['value'])
                ->where('T5.to', '=', $data['merchant_id'])
                ->where('T2.deleted_at', '=', null)
                ->where('T1.tags', 'like', $data['tags'])
                ->groupBy('T1.id')
                ->orderBy($con['column'], $data['sort'][$con['column']])
                ->get();
        }
        
      }else{
        $products = DB::table('products as T1')
                ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
                ->leftJoin('transferred_products as T3', 'T3.product_id', '=', 'T1.id')
                ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
                ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
                ->where($con['column'], 'like', $con['value'])
                ->where('T5.to', '=', $data['merchant_id'])
                ->where('T2.deleted_at', '=', null)
                // ->where('T3.product_attribute_id', '=', 'T2.id')
                ->select('T1.code as product_code', 'T2.payload as unit', 'T2.payload_value as unit_value', 'T3.product_attribute_id', 'T2.id')
                ->groupBy('T2.id')
                ->skip($data['offset'])->take($data['limit'])
                ->orderBy($con['column'], $data['sort'][$con['column']])
                ->get();
        $size = DB::table('products as T1')
              ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
              ->leftJoin('transferred_products as T3', 'T3.product_id', '=', 'T1.id')
              ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
              ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
              ->where($con['column'], 'like', $con['value'])
              ->where('T2.deleted_at', '=', null)
              ->where('T5.to', '=', $data['merchant_id'])
              ->groupBy('T2.id')
              ->orderBy($con['column'], $data['sort'][$con['column']])
              ->get();
      }
    }
    else{
      $products = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where($con['column'], 'like', $con['value'])
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T1.type', '=', $productType)
          ->where('T2.deleted_at', '=', null)
          ->groupBy('T1.id')
          ->select('*', 'T1.code as product_code', 'T2.payload as unit', 'T2.payload_value as unit_value')
          ->skip($data['offset'])->take($data['limit'])
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->get();
      
      $size = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where($con['column'], 'like', $con['value'])
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T1.type', '=', $productType)
          ->where('T2.deleted_at', '=', null)
          ->groupBy('T1.id')
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->get();
    }
    if(sizeof($products) > 0){
      // $i = 0;
      // foreach ($products as $key) {
      //   $productQty = app($this->transferredProductsClass)->getTransferredProduct($products[$i]->product_id, $data['merchant_id']);
      //   $consumed = app('Increment\Marketplace\Paddock\Http\BatchProductController')->getTotalAppliedRateBySpecifiedParams($products[$i]->product_id, $data['merchant_id']);
      //   // $qty = app($this->productTraceClass)->getBalanceQtyOnManufacturer('product_id', $products[$i]->product_id);
      //   $attributes = app($this->productAttrClass)->getByParams('product_id', $products[$i]->product_id);
      //   if($productQty->qty > 0){
      //     $merchantFrom = app($this->merchantClass)->getColumnValueByParams('id', $products[$i]->from, 'name');
      //     $merchant =  app($this->merchantClass)->getColumnValueByParams('id', $products[$i]->merchant_id, 'name');
      //     $volume = $attributes ? floatval($attributes[0]['payload_value']) : 0;
      //     $array = array(
      //       'product_qty' => $productQty != null && $volume > 0 ? number_format(($productQty->qty - floatval($consumed / $volume)), 2) : 0,
      //       'unit' => $products[$i]->payload,
      //       'unit_value' => $products[$i]->payload_value,
      //       'qty' => app($this->batcProductClass)->getProductQtyTrace($products[$i]->merchant_id, 'product_id', $products[$i]->product_id, $products[$i]->payload_value, $productQty != null ? $productQty->qty : 0), 
      //     );
      //     $this->response['data'][$i]['inventory'] = $array;
      //     $this->response['data'][$i]['merchant'] = array(
      //       'name' => $merchant);
      //     $this->response['data'][$i]['merchant_from'] = $merchantFrom;
      //     $this->response['data'][$i]['manufacturing_date'] = $productQty != null ? $productQty->manufacturing_date : null;
      //     $this->response['data'][$i]['title'] = $products[$i]->title;
      //     $this->response['data'][$i]['volume'] = app($this->productAttrClass)->getProductUnits($products[$i]->product_id);
      //     $this->response['data'][$i]['product_id'] = $products[$i]->product_id;
      //     $this->response['data'][$i]['qty_in_bundled'] = 0; // $qty['qty_in_bundled'];
      //     $this->response['data'][$i]['code'] = $products[$i]->product_code;
      //     $this->response['data'][$i]['type'] = $products[$i]->type;
      //     $this->response['data'][$i]['details'] = $this->retrieveProductDetailsByParams('id', $products[$i]->product_id);
          
      //   }
      //   $i++;
      // }
      $this->manageDataEndUser($products, $data);
      $this->response['size'] = sizeOf($size);
      return $this->response();
    }else{
      $this->response['size'] = sizeOf($size);
      $this->response['data'] = [];
      return $this->response();
    }
  }

  public function manageDataEndUser($products, $data){
    $i = 0;
    foreach ($products as $key) {
      // dd($products);
      $productId = $products[$i]->product_id;
      $productQty = app($this->transferredProductsClass)->getTransferredProduct($productId, $data['merchant_id']);
      $consumed = app('Increment\Marketplace\Paddock\Http\BatchProductController')->getTotalAppliedRateBySpecifiedParams($productId, $data['merchant_id']);
      // $qty = app($this->productTraceClass)->getBalanceQtyOnManufacturer('product_id', $products[$i]->product_id);
      $attributes = app($this->productAttrClass)->getByParams('product_id', $productId);
      if($productQty->qty > 0){
        $merchantFrom = app($this->merchantClass)->getColumnValueByParams('id', $products[$i]->from, 'name');
        $merchant =  app($this->merchantClass)->getColumnValueByParams('id', $products[$i]->merchant_id, 'name');

        $qty = 0;
        $j = 0;
        foreach ($attributes as $attributeKey) {
          $productAttributeId = $attributes[$j]['id'];
          $volume = floatval($attributes[$j]['payload_value']);
          $totalProductTraces = app($this->transferredProductsClass)->getActiveProductQtyInAttribute($productId, $productAttributeId, $data['merchant_id']);
          $totalConsumed = app('Increment\Marketplace\Paddock\Http\BatchProductController')->getTotalAppliedRateByParamsByAttribute($productId, $productAttributeId, $data['merchant_id']);
          $totalConsumedInTraces = floatval($totalConsumed / $volume);
          $qty += $totalProductTraces - $totalConsumedInTraces;
          $j++;
        }
        $string = $products[$i]->unit;
        $temps = explode(' ', $string);
        $final = array_pop($temps);
        $this->response['data'][$i]['volume'] = app($this->productAttrClass)->getProductUnit($products[$i]->product_id);
        $this->response['data'][$i]['merchant'] = array('name' => $merchant);
        $this->response['data'][$i]['merchant_from'] = $merchantFrom;
        $this->response['data'][$i]['manufacturing_date'] = $productQty != null ? $productQty->manufacturing_date : null;
        $this->response['data'][$i]['title'] = $products[$i]->title;
        // $this->response['data'][$i]['volume'] = app($this->productAttrClass)->getProductUnits($productId);
        $this->response['data'][$i]['product_id'] = $products[$i]->product_id;
        $this->response['data'][$i]['qty'] = number_format($qty, 2);
        $this->response['data'][$i]['qty_in_bundled'] = 0; // $qty['qty_in_bundled'];
        $this->response['data'][$i]['code'] = $products[$i]->product_code;
        $this->response['data'][$i]['type'] = $products[$i]->type;
        $this->response['data'][$i]['details'] = $this->retrieveProductDetailsByParams('id', $productId);
        
      }
      $i++;
    }
  }
  public function retrieveProductsSecondLevelEndUser(Request $request){
    $data = $request->all();
    $con = $data['condition'];
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
    $productType = $data['productType'];
    $result = null;
    $size = null;
    $result = DB::table('merchants as T1')
        ->leftJoin('transferred_products as T2', 'T2.merchant_id', '=', 'T1.id')
        ->where($con['column'], 'like', $con['value'])
        ->where('T2.merchant_id', '=', $data['merchant_id'])
        ->where('T2.deleted_at', '=', null)
        ->groupBy('T2.product_attribute_id')
        ->select('T2.product_id', 'T2.product_attribute_id', 'T2.merchant_id')
        ->skip($data['offset'])->take($data['limit'])
        ->orderBy($con['column'], $data['sort'][$con['column']])
        ->get();
    $result = json_decode(json_encode($result), true);
    $size =  DB::table('merchants as T1')
      ->leftJoin('transferred_products as T2', 'T2.merchant_id', '=', 'T1.id')
      ->where($con['column'], 'like', $con['value'])
      ->where('T2.merchant_id', '=', $data['merchant_id'])
      ->where('T2.deleted_at', '=', null)
      ->groupBy('T2.product_attribute_id')
      ->orderBy($con['column'], $data['sort'][$con['column']])
      ->get();
    $testArray = array();
    if(sizeof($result) > 0){  
      $this->reponse['data'] = $this->manageResult2ndLevel($result, $data);
      $this->response['size'] = sizeOf($size);
      return $this->response();
    }else{
      $this->response['size'] = sizeOf($size);
      $this->response['data'] = [];
      return $this->response();
    }
  }

  public function manageResult2ndLevel($products, $data){
    $i = 0;
    foreach ($products as $key) {
      $productId = $products[$i]->product_id;
      $productData = app($this->productClass)->getProductByParams('id', $products[$i]->product_id, ['title', 'type', 'merchant_id', 'id']);
      $productQty = app($this->transferredProductsClass)->getTransferredProduct($productId, $data['merchant_id'], $products[$i]->product_attribute_id);
      // $consumed = app('Increment\Marketplace\Paddock\Http\BatchProductController')->getTotalAppliedRateBySpecifiedParams($productId, $data['merchant_id']);
      // $qty = app($this->productTraceClass)->getBalanceQtyOnManufacturer('product_id', $products[$i]->product_id);
      $attributes = app($this->productAttrClass)->getByParams('id', $products[$i]->product_attribute_id);
      if($productQty->qty > 0){
        $merchantFrom = app($this->merchantClass)->getColumnValueByParams('id', $productData['merchant_id'], 'name');
        $merchant =  app($this->merchantClass)->getColumnValueByParams('id', $products[$i]->merchant_id, 'name');

        $qty = 0;
        $j = 0;
        foreach ($attributes as $attributeKey) {
          $productAttributeId = $attributes[$j]['id'];
          $volume = floatval($attributes[$j]['payload_value']);
          $totalProductTraces = app($this->transferredProductsClass)->getActiveProductQtyInAttribute($productId, $productAttributeId, $data['merchant_id']);
          $totalConsumed = app('Increment\Marketplace\Paddock\Http\BatchProductController')->getTotalAppliedRateByParamsByAttribute($productId, $productAttributeId, $data['merchant_id']);
          $totalConsumedInTraces = floatval($totalConsumed / $volume);
          $qty += $totalProductTraces - $totalConsumedInTraces;
          $j++;
        }
        $string = $attributes[0]['payload'];
        $temps = explode(' ', $string);
        $final = array_pop($temps);
        $products[$i]['volume'] = $attributes[0]['payload_value'].''.$final;
        $products[$i]['merchant'] = array('name' => $merchant);
        $products[$i]['type'] = $productData['type'];
        $products[$i]['title'] =$productData['title'];
        $products[$i]['product_attribute_id'] = $products[$i]->product_attribute_id;
        $products[$i]['merchant_from'] = $merchantFrom;
        $products[$i]['manufacturing_date'] = $productQty != null ? $productQty->manufacturing_date : null;
        $products[$i]['qty'] = number_format($qty, 2);
        $products[$i]['qty_in_bundled'] = 0; // $qty['qty_in_bundled'];
        $products[$i]['details'] = $this->retrieveProductDetailsByParams('id', $productId);
        
      }
      $i++;
      return $products
    }
  }

  public function retrieveProductsFirstLevel(Request $request){
    $data = $request->all();
    $con = $data['condition'];
    $result = null;
    $size = null;
    $productType = $data['productType'];
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
    if($productType == 'all'){
      if(isset($data['tags'])){
        if($data['tags'] != 'other'){
          $result = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T3.status', '=', 'active')
          ->where($con['column'], 'like', $con['value'])
          ->whereNull('T3.deleted_at')
          ->skip($data['offset'])->take($data['limit'])
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->select('*', 'T4.id as productTraceId', 'T1.code as product_code', 'T5.from')
          ->groupBy('T3.product_id')
          ->get();

          $size = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T3.status', '=', 'active')
          ->where($con['column'], 'like', $con['value'])
          ->whereNull('T3.deleted_at')
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->select('*', 'T4.id as productTraceId', 'T1.code as product_code', 'T5.from')
          ->groupBy('T3.product_id')
          ->count();
        }else{
          $result = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T3.status', '=', 'active')
          ->where(function($query){
            $query->where('T1.tags', 'not like', 'herbicide')
                  ->Where('T1.tags', 'not like', 'fungicide')
                  ->Where('T1.tags', 'not like', 'insecticide');
          })
          ->whereNull('T3.deleted_at')
          ->skip($data['offset'])->take($data['limit'])
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->select('*', 'T4.id as productTraceId', 'T1.code as product_code', 'T5.from')
          ->groupBy('T3.product_id')
          ->get();

          $size = DB::table('products as T1')
            ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
            ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
            ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
            ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
            ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
            ->where('T5.to', '=', $data['merchant_id'])
            ->where('T3.status', '=', 'active')
            ->where(function($query){
              $query->where('T1.tags', 'not like', 'herbicide')
                    ->Where('T1.tags', 'not like', 'fungicide')
                    ->Where('T1.tags', 'not like', 'insecticide');
            })
            ->whereNull('T3.deleted_at')
            ->orderBy($con['column'], $data['sort'][$con['column']])
            ->select('*', 'T4.id as productTraceId', 'T1.code as product_code', 'T5.from')
            ->groupBy('T3.product_id')
            ->count();
        }
      }else{
        $result = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T3.status', '=', 'active')
          ->whereNull('T3.deleted_at')
          ->skip($data['offset'])->take($data['limit'])
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->select('*', 'T4.id as productTraceId', 'T1.code as product_code', 'T5.from')
          ->groupBy('T3.product_id')
          ->get();

          $size = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T3.status', '=', 'active')
          ->whereNull('T3.deleted_at')
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->select('*', 'T4.id as productTraceId', 'T1.code as product_code', 'T5.from')
          ->groupBy('T3.product_id')
          ->count();
      }
    }
    else{
       $result = DB::table('products as T1')
        ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
        ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
        ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
        ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
        ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
        ->where('T5.to', '=', $data['merchant_id'])
        ->where('T3.status', '=', 'active')
        ->where($con['column'], 'like', $con['value'])
        ->where('T1.type', '=', $productType)
        ->whereNull('T3.deleted_at')
        ->skip($data['offset'])->take($data['limit'])
        ->orderBy($con['column'], $data['sort'][$con['column']])
        ->groupBy('T3.product_id')
        ->select('*', 'T4.id as productTraceId', 'T1.code as product_code', 'T5.from')
        ->groupBy('T3.product_id')
        ->get();
        $size = DB::table('products as T1')
          ->leftJoin('product_attributes as T2', 'T2.product_id', '=', 'T1.id')
          ->leftJoin('merchants as T6', 'T1.merchant_id', '=', 'T6.id')
          ->leftJoin('transferred_products as T3', 'T3.product_attribute_id', '=', 'T2.id')
          ->leftJoin('product_traces as T4', 'T3.payload_value', '=', 'T4.id')
          ->leftJoin('transfers as T5', 'T3.transfer_id', '=', 'T5.id')
          ->where('T5.to', '=', $data['merchant_id'])
          ->where('T3.status', '=', 'active')
          ->where($con['column'], 'like', $con['value'])
          ->where('T1.type', '=', $productType)
          ->whereNull('T3.deleted_at')
          ->orderBy($con['column'], $data['sort'][$con['column']])
          ->groupBy('T3.product_id')
          ->select('*')
          ->groupBy('T3.product_id')
          ->count();
    }
    if(sizeof($result)){
      $temp =  json_decode(json_encode($result), true);
      $i=0; 
      foreach($temp as $key){
          unset($temp[$i]['deleted_at']);
          unset($temp[$i]['updated_at']);
          unset($temp[$i]['created_at']);
          unset($temp[$i]['payload']);
          unset($temp[$i]['price_settings']);
          $merchantFrom = app($this->merchantClass)->getColumnValueByParams('id', $key['from'], 'name');
          $merchant =  app($this->merchantClass)->getColumnValueByParams('id', $key['merchant_id'], 'name');
          $temp[$i]['title']     = $key['title'];
          $temp[$i]['id']        = $key['id'];
          $temp[$i]['merchant']  = array(
            'name' => $merchant);
          $temp[$i]['merchant_from'] = $merchantFrom;
          $temp[$i]['qty']   = app($this->transferredProductsClass)->getTransferredProduct($temp[$i]['product_id'], $data['merchant_id'], $temp[$i]['product_attribute_id'])->qty;
          $temp[$i]['volume'] = app($this->productAttrClass)->getProductUnits('id', $temp[$i]['product_attribute_id']);
          $temp[$i]['qty_in_bundled'] = $this->getBundledProducts($data['merchant_id'], $key['id']);
          $temp[$i]['type']    = $key['type'];
          $temp[$i]['details'] = json_decode($key['details'], true);
          $temp[$i]['batch_number'] = isset($key['batch_number']) ? $key['batch_number'] : null;
          $temp[$i]['manufacturing_date'] = isset($key['manufacturing_date']) ? $key['manufacturing_date'] : null;
          $temp[$i]['details'] = $this->retrieveProductDetailsByParams('id', $key['product_id']);
        $i++;
      }
      $this->response['data'] = $temp;
      $this->response['size'] = $size;
      return $this->response();    
    }else{
      $this->response['data'] = [];
      $this->response['size'] = $size;
      return $this->response();
    }
  }

  public function retrieveProductsSecondLevel(Request $request){
    $data = $request->all();
    $con = $data['condition'];
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
    $productType = $data['productType'];
    $result = null;
    $size = null;
    if($productType == 'all'){
      $result = DB::table('transferred_products as T1')
      ->leftJoin('products as T2', 'T2.id', '=', 'T1.product_id')
      ->leftJoin('merchants as T3', 'T2.merchant_id', '=', 'T3.id')
      ->leftJoin('transfers as T4', 'T1.transfer_id', '=', 'T4.id')
      ->leftJoin('product_traces as T5', 'T1.payload_value', '=', 'T5.id')
      ->where('T5.to', '=', $data['merchant_id'])
      ->where('T1.status', '=', 'active')
      ->where('T3.name', 'like', $con['value'])
      ->whereNull('T1.deleted_at')
      ->skip($data['offset'])->take($data['limit'])
      ->orderBy($con['column'], $data['sort'][$con['column']])
      ->select('*', 'T2.title', 'T2.code', 'T4.from')
      ->groupBy('T1.product_id')
      ->get();

      $size = DB::table('transferred_products as T1')
      ->leftJoin('products as T2', 'T2.id', '=', 'T1.product_id')
      ->leftJoin('merchants as T3', 'T2.merchant_id', '=', 'T3.id')
      ->leftJoin('transfers as T4', 'T1.transfer_id', '=', 'T4.id')
      ->leftJoin('product_traces as T5', 'T1.payload_value', '=', 'T5.id')
      ->where('T5.to', '=', $data['merchant_id'])
      ->where('T1.status', '=', 'active')
      ->where('T3.name', 'like', $con['value'])
      ->whereNull('T1.deleted_at')
      ->skip($data['offset'])->take($data['limit'])
      ->orderBy($con['column'], $data['sort'][$con['column']])
      ->select('*', 'T2.title', 'T2.code', 'T4.from')
      ->groupBy('T1.product_id')
      ->count();

    }else{
      $result = DB::table('transferred_products as T1')
      ->leftJoin('products as T2', 'T2.id', '=', 'T1.product_id')
      ->leftJoin('merchants as T3', 'T2.merchant_id', '=', 'T3.id')
      ->leftJoin('transfers as T4', 'T1.transfer_id', '=', 'T4.id')
      ->leftJoin('product_traces as T5', 'T1.payload_value', '=', 'T5.id')
      ->where('T5.to', '=', $data['merchant_id'])
      ->where('T1.status', '=', 'active')
      ->where('T3.name', 'like', $con['value'])
      ->where('T2.type', '=', $productType)
      ->whereNull('T1.deleted_at')
      ->skip($data['offset'])->take($data['limit'])
      ->orderBy($con['column'], $data['sort'][$con['column']])
      ->select('*', 'T2.title', 'T2.code', 'T4.from')
      ->groupBy('T1.product_id')
      ->get();

      $size = DB::table('transferred_products as T1')
      ->leftJoin('products as T2', 'T2.id', '=', 'T1.product_id')
      ->leftJoin('merchants as T3', 'T2.merchant_id', '=', 'T3.id')
      ->leftJoin('transfers as T4', 'T1.transfer_id', '=', 'T4.id')
      ->leftJoin('product_traces as T5', 'T1.payload_value', '=', 'T5.id')
      ->where('T5.to', '=', $data['merchant_id'])
      ->where('T1.status', '=', 'active')
      ->where('T3.name', 'like', $con['value'])
      ->where('T2.type', '=', $productType)
      ->whereNull('T1.deleted_at')
      ->orderBy($con['column'], $data['sort'][$con['column']])
      ->select('*', 'T2.title', 'T2.code', 'T4.from')
      ->groupBy('T1.product_id')
      ->count();
    }

    $testArray = array();
    if(sizeof($result) > 0){  
      $temp =  json_decode(json_encode($result), true);
      $i=0; 
      foreach($temp as $key){
          unset($temp[$i]['deleted_at']);
          unset($temp[$i]['updated_at']);
          unset($temp[$i]['created_at']);
          unset($temp[$i]['payload']);
          unset($temp[$i]['price_settings']);
          $merchantFrom = app($this->merchantClass)->getColumnValueByParams('id', $key['from'], 'name');
          $merchant =  app($this->merchantClass)->getColumnValueByParams('id', $key['merchant_id'], 'name');
          $temp[$i]['title']     = $key['title'];
          $temp[$i]['id']        = $key['id'];
          $temp[$i]['merchant']  = array(
            'name' => $merchant);
          $temp[$i]['merchant_from'] = $merchantFrom;
          $temp[$i]['qty']  = app($this->transferredProductsClass)->getTransferredProduct($temp[$i]['product_id'], $data['merchant_id'])->qty;
          $temp[$i]['qty_in_bundled'] = $this->getBundledProducts($data['merchant_id'], $key['id']);
          $temp[$i]['type']    = $key['type'];
          $temp[$i]['details'] = $this->retrieveProductDetailsByParams('id', $key['product_id']);
          
        $i++;
      }
      $this->response['data'] = $temp;
      $this->response['size'] = $size;
      return $this->response();
    }else{
      $this->response['data'] = [];
      $this->response['size'] = $size;
      return $this->response();
    }
  }

  public function getBundledProducts($merchantId, $productId){
    $result = TransferredProduct::where('merchant_id', '=', $merchantId)->where('product_id', '=', $productId)->where('status', '=', 'in_bundled')->count();
    return $result;
  }
    
  public function retrieveByPagination(Request $request){
    $data = $request->all();
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
    $result = DB::table('transfers as T1')
    ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
    ->where('T1.to', '=', $data['merchant_id'])
    ->where('T2.deleted_at', '=', null)
    ->where('T1.deleted_at', '=', null)
    ->get();
    $result = $result->groupBy('product_id');
    $i = 1;
    $size = $result->count();
    $testArray = array();
    if(sizeof($result) > 0){  
      foreach($result as $key){
        foreach($key as $innerKey){
          $item = array(
            'code'  =>  $innerKey->code,
            'name'  =>  $this->retrieveName($innerKey->account_id),
            'number_of_items'   =>  app($this->transferredProductsClass)->getSizeNoDate('transfer_id', $innerKey->id),
            'trasferred_on' => Carbon::createFromFormat('Y-m-d H:i:s', $innerKey->created_at)->copy()->tz($this->response['timezone'])->format('F j, Y H:i A'),
            'to'    => app($this->merchantClass)->getColumnValueByParams('id', $innerKey->to, 'name'),
            'from'  => app($this->merchantClass)->getColumnValueByParams('id', $innerKey->from, 'name')
          );
          if(!in_array($item, $testArray)){
            $count = count($testArray);
            if($count >= $data['limit']){
              array_shift($testArray);
            }
            $testArray[] = $item;
          }
        } 
        $this->response['data'] = $testArray;
      break;
      }
    }
    $this->response['size'] = $size;
    return $this->response();
  }

  public function retrieveProductTitle(Request $request){
    $data = $request->all();
    $result =DB::table('transferred_products as T1')
      ->leftJoin('products as T3', 'T3.id', '=', 'T1.product_id')
      ->select(['T3.*', 'T1.product_attribute_id'])
      ->where('T1.merchant_id', '=', $data['merchant_id'])
      ->where('T1.deleted_at', '=', null)
      ->get();
    $result = $result->groupBy('id');
    $i = 1;
    if(sizeof($result) > 0){
      // dd($result);
      $i = 0;
      // print_r($result);
      foreach($result as $key => $value){
        // print_r($result[$i]);
        $attributes = sizeof($value) > 0 ? $value->groupBy('product_attribute_id') : null;
        $variation = [];
        $product = null;
        if($attributes){
          foreach ($attributes as $jKey => $jValue) {
            $productAttribute = app($this->productAttrClass)->getAttributeByParams('id', $jKey);
            $product = sizeof($jValue) > 0 ? $jValue[0] : null;
            if($productAttribute){
              $variation[] = $productAttribute;
            }
          }
        }else{
          $variation = null;
        }
        $item = array(
          'merchant' => $product ?  app($this->merchantClass)->getColumnValueByParams('id', $product->merchant_id, 'name') : null,
          'description' => $product ? $product->description : null,
          'title' => $product ? $product->title : null,
          'tags' => $product ? $product->tags : null,
          'details' => $product ? $this->retrieveProductDetailsByParams('id', $product->id) : null,
          'unit' => $variation,
          'id' => $product ? $product->id : null,
          'variation' => $variation
        );
        $i++;
        $testArray[] = $item;
      }
    }
    $this->response['data'] = $testArray;
    // $this->response['size'] = 0;
    return $this->response();
  }
    public function retrieveTransferredItems(Request $request){
      $data = $request->all();
      $this->retrieveDB($data);
      if(sizeof($this->response['data']) <= 0){
        return $this->response();
      }
      $result = app($this->transferredProductsClass)->getAllByParamsOnly('transfer_id', $this->response['data'][0]['id']);
      if(sizeof($result) > 0){
        $array = array();
        foreach ($result as $key) {
          $trace = app($this->productTraceClass)->getByParamsDetails('id', $key['payload_value']);
          $attributes = app($this->productAttrClass)->getProductUnits('id', $trace[0]['product_attribute_id']);

          $item = array(
            'title'         => $trace[0]['product']['title'],
            'batch_number'  => $trace[0]['batch_number'],
            'manufacturing_date' => $trace[0]['manufacturing_date'],
            'product_attribute' => $attributes
          );
          $array[] = $item;
        }
        $this->response['data'] = $array;
      }
      return $this->response();
    }

    public function manageQtyWithBundled($product, $productTrace){
      if($product['type'] != 'regular'){
        $bundled = app($this->bundledProductController)->getProductsByParamsNoDetailsDBFormat('bundled_trace', $productTrace);
        $this->response['others'] = $bundled;
        foreach ($bundled as $key) {
          $product = null;
          $index = null;
          $index = array_search(intval($key->product_on_settings), array_column($this->response['data'], 'id'), true);
          if(is_int($index)){
            $this->response['data'][$index]['qty_in_bundled'] += intval($key->size);
          }else{
            $product =  app($this->productClass)->getProductByParamsConsignments('id', intval($key->product_on_settings));
            $product['qty'] = 0;
            $product['qty_in_bundled'] = intval($key->size);
            $this->response['data'][] = $product;
          }
        }
      }
    }

    public function getQtyTransferred($merchantId, $productId){
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T1.to', '=', $merchantId)
      ->where('T2.product_id', '=', $productId)
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->get(['T2.*']);
      $result = $result->groupBy('product_id');
      $qty = 0;
      foreach ($result as $key => $value) {
        foreach ($value as $keyInner) {
          $tSize = app($this->transferredProductsClass)->getSize('payload_value', $keyInner->payload_value, $keyInner->created_at);
          $bundled = app($this->bundledProductController)->getByParamsNoDetails('product_trace', $keyInner->payload_value);
          if($tSize == 0 && $bundled == null){
            $qty++;
          }
        }
      }
      return $qty;
    }

    public function getOwn($traceId){
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T2.payload_value', '=', $traceId)
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->orderBy('T2.created_at', 'desc')
      ->first(['T1.id as t_id', 'T1.*', 'T2.*']);
      return $result;
    }

    public function basicRetrieve(Request $request){
      $data = $request->all();
      $this->model = new Transfer();
      $this->retrieveDB($data);
      return $this->response();
    }

    public function getProductTraceByOrderId($orderRequestId, $productId){
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T1.order_request_id', '=', $orderRequestId)
      ->where('T2.product_id', '=', $productId)
      ->limit(1)
      ->get(['T2.payload_value']);
      if(sizeof($result)){
        return app($this->productTraceClass)->getByParamsWithoutLimit('id', $result[0]->payload_value);
      }else{
        return null;
      }
    }
}
