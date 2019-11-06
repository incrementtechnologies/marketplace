<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\BundledSetting;
use Carbon\Carbon;
class BundledSettingController extends APIController
{
  
  public $productController = 'Increment\Marketplace\Http\ProductController';
  
  function __construct(){
    $this->model = new BundledSetting();
  }


  public function retrieve(Request $request){
    $data = $request->all();
    $this->model = new BundledSetting();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['product'] = app($this->productController)->getByParams('id', $result[$i]['product_id']);
        $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
        $i++;
      }
    }
    return $this->response();
  }
  public function getByParams($column, $value){
    $result = BundledSetting::where($column, '=', $value)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $result[$i]['product'] = app($this->productController)->getByParams('id', $result[$i]['product_id']);
        $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
        $i++;
      }
    }
    return sizeof($result) > 0 ? $result : null;
  }

  public function getByParamsDetails($column, $value){
    $result = BundledSetting::where($column, '=', $value)->get();
    return sizeof($result) > 0 ? $result : null;
  }
}
