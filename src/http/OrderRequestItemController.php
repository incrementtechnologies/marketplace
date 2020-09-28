<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\OrderRequestItem;
use Carbon\Carbon;
class OrderRequestItemController extends APIController
{

  public $productClass = 'Increment\Marketplace\Http\ProductController';
  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';

  function __construct(){
    $this->model = new OrderRequestItem();
  }

  public function create(Request $request){
    $data = $request->all();
    dd($data);
    if($this->checkIfExist($data['order_request_id'], $data['product_id']) == true){
      $this->response['error'] = 'Already exist to the list!';
      $this->response['data'] = null;
      return $this->response();
    }
    $this->model = new OrderRequestItem();
    $this->insertDB($data);
    return $this->response();
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $array = array();
      foreach ($result as $key) {
        $product = app($this->productClass)->getByParams('id', $key['product_id']);
        $item = array(
          'title'   => $product ? $product['title'] : null,
          'id'      => $key['id'],
          'qty'     => $key['qty'],
          'counter' => 0,
          'product_id'     => $key['product_id'],
          'order_request_id'     => $key['order_request_id'],
          'merchant'     => $product ? app($this->merchantClass)->getColumnValueByParams('id', $product['merchant_id'], 'name') : null
        );
        $array[] = $item;
      }
      $this->response['data'] = $array;
    }
    return $this->response();
  }

  public function checkIfExist($orderRequestId, $productId){
    $result = OrderRequestItem::where('order_request_id', '=', $orderRequestId)->where('product_id', '=', $productId)->get();
    return sizeof($result) > 0 ? true : false;
  }
}
