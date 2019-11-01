<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\BundledProduct;
use Carbon\Carbon;
class BundledProductController extends APIController
{
  
  public $productTraceController = 'Increment\Marketplace\Http\ProductTraceController';
  
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
          'product_id'     => $data['product_id'],
          'product_trace' => $data['products_traces'][$i]['id'],
          'bundled_trace' => $data['bundled_trace'],
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

  public function retrieve(Request $request){
    $data = $request->all();
    $this->model = new BundledProduct();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['product_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['product_trace']);
        $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
        $i++;
      }
    }
    return $this->response();
  }

  public function getByParams($column, $value){
    $result = BundledProduct::where($column, '=', $value)->where('deleted_at', '=', null)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $result[$i]['product_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['product_trace']);
        $result[$i]['bundled_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['bundled_trace']);
        $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
        $i++;
      }
    }
    return sizeof($result) > 0 ? $result : null;
  }
}
