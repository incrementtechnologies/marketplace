<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\OrderRequestItem;
use Carbon\Carbon;
class OrderRequestItemController extends APIController
{

  public $productClass = 'Increment\Marketplace\Http\ProductController';

  function __construct(){
    $this->model = new OrderRequestItem();
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $array = array();
      foreach ($result as $key) {
        $item = array(
          'title'   => app($this->productClass)->getProductColumnByParams('id', $key['product_id'], 'title'),
          'id'      => $key['id'],
          'qty'     => $key['qty'],
          'product_id'     => $key['product_id']
        );
        $array[] = $item;
      }
      $this->response['data'] = $array;
    }
    return $this->response();
  }
}
