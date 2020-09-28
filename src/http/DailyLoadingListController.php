<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\DailyLoadingList;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class DailyLoadingListController extends APIController
{

  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
  public $orderRequestClass = 'Increment\Marketplace\Http\OrderRequestController';
  public $productClass = 'Increment\Marketplace\Http\ProductController';

  function __construct(){
    $this->model = new DailyLoadingList();
  }

  public function create(Request $request){
    $data = $request->all();
    
    if($this->checkIfExist('order_request_id', $data['order_request_id']) == true){
      $this->response['error'] = 'Already exist to the list!';
      $this->response['data'] = null;
      return $this->response();
    }

    $data['code'] = $this->generateCode();
    $data['status'] = 'pending';
    $this->model = new DailyLoadingList();
    $this->insertDB($data);

    if($this->response['data'] > 0){
      app($this->orderRequestClass)->updateByParams($data['order_request_id'], array(
        'delivered_by' => $data['account_id'],
        'status'       => 'in_progress',
        'updated_at'   => Carbon::now()
      ));
    }
    return $this->response();
  }

  public function retrieveBasic(Request $request){
    $data = $request->all();
    $tempResult = DB::table('daily_loading_lists as T1')
      ->select([
          DB::raw("SQL_CALC_FOUND_ROWS id")
      ])
      ->join('order_requests as T2', 'T2.id', '=', 'T1.order_request_id')
      ->where('T1.merchant_id', '=', $data['merchant_id'])
      ->where('T1.account_id', '=', $data['account_id'])
      ->where('T1.status', '=', $data['status'])
      ->where('T1.deleted_at', '=', null)
      ->orderBy($data['sort']['column'], $data['sort']['value'])
      ->select(['T2.*', 'T1.id AS daily_loading_list_id'])
      ->offset($data['offset'])
      ->limit($data['limit'])
      ->get();
    $this->response['size'] = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
    $results = json_decode($tempResult, true);
    if(sizeof($results) > 0){
      $this->response['data'] = app($this->orderRequestClass)->manageResults($results);
    }
    return $this->response();
  }

  public function retrieveSummaryTotal(Request $request){
    $data = $request->all();
    
    $tempResult = DB::table('daily_loading_lists as T1')
      ->join('order_request_items as T2', 'T2.order_request_id', '=', 'T1.order_request_id')
      ->where('T1.merchant_id', '=', $data['merchant_id'])
      ->where('T1.account_id', '=', $data['account_id'])
      ->where('T1.deleted_at', '=', null)
      ->where('T2.deleted_at', '=', null)
      ->where('T1.status', '=', 'pending')
      ->select(['T2.*', 'T1.id as daily_loading_list_id'])
      ->get();

    $results = json_decode($tempResult->groupBy('product_id'), true);
    
    if(sizeof($results) > 0){
      foreach ($results as $key => $value) {
        $array = null;
        $totalQty = 0;
        $product = app($this->productClass)->getByParams('id', $key);
        $orderRequestId = null;
        $dailyLoadinglistId = null;
        $merchant = $product ? app($this->merchantClass)->getColumnValueByParams('id', $product['merchant_id'], 'name') : null;
        foreach ($value as $keyValues) {
          $totalQty += intval($keyValues['qty']);
          $orderRequestId = $keyValues['order_request_id'];
          $dailyLoadinglistId = $keyValues['daily_loading_list_id'];
        }
        $this->response['data'][] = array(
          'merchant'  => $merchant == null ? null : array(
            'name'   => $merchant,
            'id'     => $product['merchant_id']
          ),
          'title'     => $product ? $product['title'] : null,
          'qty'       => $totalQty,
          'daily_loading_list_id' => $dailyLoadinglistId,
          'order_request_id' => $orderRequestId,
          'product_id' => $key,
          'counter'     => 0
        );
      }
    }
    
    return $this->response();
  }

  public function approved(Request $request){
    $data = $request->all();
    DailyLoadingList::where('merchant_id', '=', $data['merchant_id'])->where('account_id', '=', $data['account_id'])->update(array(
      'status'  => 'approved',
      'updated_at' => Carbon::now()
    ));
    return $this->response();
  }

  public function generateCode(){
    $code = 'DLL_'.substr(str_shuffle($this->codeSource), 0, 61);
    $codeExist = DailyLoadingList::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }

  public function checkIfExist($column, $value){
    $result = DailyLoadingList::where($column, '=', $value)->get();
    return sizeof($result) > 0 ? true : false;
  }

  public function updateByParams($column, $value, $array){
    return DailyLoadingList::where($column, '=', $value)->update($array);
  }
}
