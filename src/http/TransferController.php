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
    public $productTraceClass = 'Increment\Marketplace\Http\ProductTraceController';
    public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
    public $landBlockProductClass = 'App\Http\Controllers\LandBlockProductController';
    function __construct(){
      $this->model = new Transfer();
      $this->localization();
    }

    public function create(Request $request){
      $data = $request->all();
      $data['code'] = $this->generateCode();
      $this->insertDB($data);
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
          'sort' => $sort
        );
        $this->model = new Transfer();
        $this->retrieveDB($parameter);
        $result = $this->response['data'];
      }else if($data['column'] == 'username'){
        $tempResult = DB::table('transfers as T1')
          ->join('accounts as T2', 'T2.id', '=', 'T1.from')
          ->where('T2.username', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->get();
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }else if($data['column'] == 'name'){
        $tempResult = DB::table('transfers as T1')
          ->join('merchants as T2', 'T2.id', '=', 'T1.to')
          ->where('T2.name', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->get();
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
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetailsTransfer($result[$i]['account_id']);
          $i++;
        }
      }

      return $this->response();
    }

    public function retrieveConsignments(Request $request){
      $data = $request->all();
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T1.to', '=', $data['merchant_id'])
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->limit(10)
      ->get(['T2.product_id', 'T2.created_at', 'T2.payload_value']);
      $result = $result->groupBy('product_id');
      // $this->response['data'] = $result;
      // return $this->response();
      $i = 0;
      $testArray = array();
      foreach ($result as $key => $value) {
        $size = 1;
        $bundledQty = 0;
        $productTrace = null;
        $test = null;
        foreach ($value as $keyInner) {
          $productTrace = $keyInner->payload_value;
          $tSize = app($this->transferredProductsClass)->getSizeLimit('payload_value', $keyInner->payload_value, $keyInner->created_at);

          if($tSize > 0){
            $size = 0;
            $test = $tSize;
          }

          $bundled = app($this->bundledProductController)->getByParamsNoDetailsWithLimit('product_trace', $keyInner->payload_value, 1);
          $trace = app($this->productTraceClass)->getByParamsByFlag('id', $productTrace);

          if($tSize == 0 && $bundled == null && $trace == true && $data['type'] == 'USER'){
            $size = 0;
            // only to end user
            // should add user type on the parameter
            $comsumed = 0;
            $comsumed = app($this->landBlockProductClass)->getTotalConsumedByTrace($data['merchant_id'], $productTrace, $keyInner->product_id);
            $size += (1 - $comsumed);
          }

          if($bundled != null){
            $bundledTransferred = TransferredProduct::where('payload_value', '=', $bundled['bundled_trace'])->where('deleted_at', '=', null)->limit(1)->count();
            if($bundledTransferred == 0){
              $bundledQty++;
            }
          }
          $testArray[] = array(
            'product_id' => $keyInner->product_id,
            'trace' =>  $keyInner->payload_value,
            'test'  => $test
          );
        }
        // if($size > 0){
        //   $product =  app($this->productClass)->getProductByParamsConsignments('id', $key);
        //   $product['qty'] = $size;
        //   $product['qty_in_bundled'] = $bundledQty;
        //   $product['productTrace'] = $productTrace;
        //   $product['test'] = $test;
        //   $this->response['data'][] = $product;
        //   $this->manageQtyWithBundled($product, $productTrace);
        //   $i++;
        // }
      }
      $this->response['data'] = $result;
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
}
