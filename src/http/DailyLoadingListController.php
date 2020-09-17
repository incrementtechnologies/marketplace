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
      ->orderBy($data['sort']['column'], $data['sort']['value'])
      ->select('T2.*')
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
      ->groupBy('product_id')
      ->select(['T2.merchant_id', 'T2.product_id', 'T1.id as daily_loading_list_id', DB::raw('COUNT(order_request_items.qty) as qty')])
      ->get();

    $results = json_decode($tempResult, true);
    
    if(sizeof($results) > 0){
      $this->response['data'] =$results;
    }
    
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
}
