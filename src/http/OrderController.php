<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Checkout;
use Increment\Marketplace\Models\CheckoutItem;
class OrderController extends APIController
{

  function __construct(){
    $this->model = new Checkout();
  }

  public function retrieveOrderItems(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['items'] = $this->getOrderItems($result[$i]['id']);
        $i++;
      }
    }
    
    return $this->response();
  }

  public function retrieveOrders(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['order_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y');
        $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
        $i++;
      }
    }
    return $this->response();
  }

  public function getOrderItems($checkoutId){
    $result = CheckoutItem::where('checkout_id', '=', $checkoutId)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $payload = $result[$i]['payload'];
        $payloadValue = $result[$i]['payload_value'];
        $i++;
      }
      return $result;
    }else{
      return null;
    }
  }

}
