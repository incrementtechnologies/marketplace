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
          $this->response['data'][$i]['transferred_products'] = app($this->transferredProductsClass)->getByParams('transfer_id', $result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
          $this->response['data'][$i]['to_details'] = app($this->merchantClass)->getByParams('id', $result[$i]['to']);
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $i++;
        }
      }

      return $this->response();
    }

    public function retrieveConsignments(Request $request){
      $data = $request->all();
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->join('products as T3', function($join){
          $join->on('T3.id', '=', 'T2.product_id')->where('T3.type', (isset($data['type']) && $data['type'] != 'regular') ? '!' : '=', 'regular');
       })
      ->where('T1.to', '=', $data['merchant_id'])
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->get(['T2.*']);

      $result = $result->groupBy('product_id');
      $i = 0;
      $this->response['data'] = array();
      foreach ($result as $key => $value) {
        $size = 0;
        $bundledQty = 0;
        $productTrace = null;
        foreach ($value as $keyInner) {
          $productTrace = $keyInner->payload_value;
          $tSize = app($this->transferredProductsClass)->getSize('payload_value', $keyInner->payload_value, $keyInner->created_at);
          $bundled = app($this->bundledProductController)->getByParamsNoDetailsWithLimit('product_trace', $keyInner->payload_value, 1);
          $trace = app($this->productTraceClass)->getByParamsByFlag('id', $productTrace);
          if($tSize == 0 && $bundled == null && $trace == true){
            $comsumed = 0;
            $comsumed = app($this->landBlockProductClass)->getTotalConsumedByTrace($data['merchant_id'], $keyInner->payload_value, $keyInner->product_id);
            $size += (1 - $comsumed);
          }
          if($bundled != null){
            $bundledTransferred = TransferredProduct::where('payload_value', '=', $bundled['bundled_trace'])->where('deleted_at', '=', null)->limit(1)->get();
            if(sizeof($bundledTransferred) == 0){
              $bundledQty++;
            }
          }
        }
        if($size > 0){
          $product =  app($this->productClass)->getProductByParams('id', $key);
          $product['qty'] = $size;
          $product['qty_in_bundled'] = $bundledQty;
          $this->response['data'][] = $product;
          $this->manageQtyWithBundled($product, $productTrace);
          $i++;
        }
      }
      return $this->response();
    }

    public function manageQtyWithBundled($product, $productTrace){
      if($product['type'] != 'regular'){
        $bundled = app($this->bundledProductController)->getProductsByParamsNoDetailsDBFormat('bundled_trace', $productTrace);
        $bundled = $bundled->groupBy('product_on_settings');
        foreach ($bundled as $key => $value) {
          $product = null;
          $index = null;
          $index = array_search(intval($key), array_column($this->response['data'], 'id'), true);
          if(is_int($index)){
            $this->response['data'][$index]['qty_in_bundled'] += sizeof($value);
          }else{
            $product =  app($this->productClass)->getProductByParams('id', intval($key));
            $product['qty'] = 0;
            $product['qty_in_bundled'] = sizeof($value);
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
