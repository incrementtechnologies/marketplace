<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\BundledProductList;
use Carbon\Carbon;
class BundledProductListController extends APIController
{
  
  public $productTraceController = 'Increment\Marketplace\Http\ProductTraceController';
  
  function __construct(){
    $this->model = new BundledProductList();
  }

  public function createByParams($merchantId, $productId){
    $model = new BundledProductList();
    $model->merchant_id = $merchantId;
    $model->product_id = $productId;
    $model->save();
  }
  public function getByParams($column, $value){
    $result = BundledProductList::where($column, '=', $value)->get();
    // if(sizeof($result) > 0){
    //   $i = 0;
    //   foreach ($result as $key) {
    //     $result[$i]['product_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['product_trace_id']);
    //     $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
    //     $i++;
    //   }
    // }
    return sizeof($result) > 0 ? $result : null;
  }
}
