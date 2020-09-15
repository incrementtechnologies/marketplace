<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\OrderRequest;
use Carbon\Carbon;
class OrderRequestController extends APIController
{

  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
  public $dailyLoadingListClass = 'Increment\Marketplace\Http\DailyLoadingListController';
  function __construct(){
    $this->model = new OrderRequest();
    $this->notRequired = array('date_delivered', 'delivered_by');
  }

  public function create(Request $request){
    $data = $request->all();
    $data['code'] = $this->generateCode();
    $merchant = app($this->merchantClass)->getColumnByParams('id', $data['merchant_id'], 'prefix');
    $counter = OrderRequest::where('merchant_id', '=', $data['merchant_id'])->count();
    $data['status'] = 'pending';
    $data['order_number'] = $merchant ? $merchant['prefix'].$this->toCode($counter): $this->toCode($counter);
    $this->model = new OrderRequest();
    $this->insertDB($data);
    return $this->response();
  }

  public function toCode($size){
    $length = strlen((string)$size);
    $code = '00000000';
    return substr_replace($code, $size, intval(7 - $length));
  }


  public function generateCode(){
    $code = 'OR_'.substr(str_shuffle($this->codeSource), 0, 61);
    $codeExist = OrderRequest::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $this->response['data'] = $this->manageResults($result);
    }
    $this->response['size'] = OrderRequest::where($data['condition'][0]['column'], '=', $data['condition'][0]['value'])->count();
    return $this->response();
  }

  public function manageResults($result){
    $array = array();
    foreach ($result as $key) {
      $item = array(
        'merchant_to' => app($this->merchantClass)->getColumnByParams('id', $key['merchant_to'], ['name', 'address']),
        'date_of_delivery'  => Carbon::createFromFormat('Y-m-d H:i:s', $key['date_of_delivery'])->copy()->tz($this->response['timezone'])->format('F j, Y'),
        'status'        => $key['status'],
        'delivered_by'  => $key['delivered_by'] ? $this->retrieveName($key['delivered_by']) : null,
        'delivered_date'=> null,
        'code'          => $key['code'],
        'added_by'      => $key['code'],
        'id'      => $key['id'],
        'order_number'      => $key['order_number'],
        'daily_loading_list' => app($this->dailyLoadingListClass)->checkIfExist('order_request_id', $key['id'])
      );
      $array[] = $item;
    }
    return $array;
  }

  public function updateByParams($id, $array){
    return OrderRequest::where('id', '=', $id)->update($array);
  }
}
