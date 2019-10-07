<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\BundledProduct;
class BundledProductController extends APIController
{
  function __construct(){
    $this->model = new BundledProduct();
  }

  public function create(Request $request){
    $data = $request->all();
    if(sizeof($data['products_traces']) > 0){
      $array = array();
      for ($i=0; $i < sizeof($data['products_traces']); $i++) {
        $array[] = array(
          'account_id' => $data['account_id'],
          'parent'     => $data['product_id'],
          'product_id' => $data['products_traces'][$i]['id'],
          'created_at'    => Carbon::now()
        );
      }
      BundledProduct::insert($array);
      $this->response['data'] = true;
    }else{
      $this->response['data'] = false;
    }
    return $this->response();
  }
}
