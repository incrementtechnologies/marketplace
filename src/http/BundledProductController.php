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
          'product_on_settings' => $data['product_on_settings'],
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

  public function insertData($data, $bundledTrace){
    $i = 0;
    foreach ($data as $key) {
      $data[$i]['created_at'] = Carbon::now();
      $data[$i]['bundled_trace'] = $bundledTrace;
      $i++;
    }
    $result = BundledProduct::insert($data);
    return $result ? true : false;
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

  public function getRemainingQty($bundledTrace, $productOnSettings){
    $qty = BundledProduct::where('bundled_trace', '=', $bundledTrace)->where('product_on_settings', '=', $productOnSettings)->where('deleted_at', '=', null)->count();
    return $qty;
  }

  public function checkIfExist($bundledTrace, $productTrace){
    $result = BundledProduct::where('bundled_trace', '=', $bundledTrace)->where('product_trace', '=', $productTrace)->where('deleted_at', '=', null)->get();
    return sizeof($result) > 0 ? true : false;
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

  public function getByParamsNoDetails($column, $value){
    $result = BundledProduct::where($column, '=', $value)->where('deleted_at', '=', null)->get();
    return sizeof($result) > 0 ? $result[0] : null;
  }

  public function updateDeletedAt(Request $request){
    $data = $request->all();
    BundledProduct::where('bundled_trace', '=', $data['bundled_trace'])->where('product_trace', '=', $data['product_trace'])->update(
      array(
        'deleted_at' => Carbon::now()
    ));
    return array(
      'data' => true,
      'error' => null,
      'timestamps' => Carbon::now()
    );
  }

  public function delete(Request $request){
    $data = $request->all();
    BundledProduct::where('bundled_trace', '=', $data['bundled_trace'])->update(
      array(
        'deleted_at' => Carbon::now()
      )
    );
    app($this->productTraceController)->deleteByParams($data['bundled_trace']);
    $this->response['data'] = true;
    return $this->response();
  }
}
