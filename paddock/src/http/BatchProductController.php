<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\BatchProduct;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatchProductController extends APIController
{
  //
  function __construct(){
    $this->model = new BatchProduct();
    $this->notRequired = array();
  }
  
  public function getProductQtyTrace($merchantId, $column, $value, $attr, $productQty){
    $result = DB::table('batch_products as T1')
      ->where($column, '=', $value)
      ->where('merchant_id', '=', $merchantId)
      ->select(DB::raw('Sum(applied_rate) as sum_applied_rate, Count(T1.product_id) as total_products'))->get();
      $result[0]->sum_applied_rate = $result[0]->sum_applied_rate == null ? 0 : $result[0]->sum_applied_rate;
    
    $totalAppliedRateVolume = ($result[0]->sum_applied_rate / (int)$attr);
    $result[0]->total_product_volume = ($attr * $productQty);
    $result[0]->total_applied_rate_volume = $totalAppliedRateVolume;
    $result[0]->total_remaining_product = number_format((float)($productQty - $totalAppliedRateVolume), 2, '.', '');
    return $result;
  }

  public function getProductTraceQty($productTraceId, $productAttr, $productQty){
    $result = DB::table('batch_products')
    ->where('product_trace_id', '=', $productTraceId)
    ->select(DB::raw('Sum(applied_rate) as sum_applied_rate'))->get();
    
    if($result[0]->sum_applied_rate > 0){
      $totalAppliedRateVolume = ($result[0]->sum_applied_rate / $productAttr[0]->payload_value );
      $result[0]->total_product_volume = ($productAttr[0]->payload_value * $productQty);
      $result[0]->total_applied_rate_volume = $totalAppliedRateVolume;
      $result[0]->total_remaining_product = number_format((float)($productQty - $totalAppliedRateVolume), 2, '.', '');
        
    }else{
      $result[0]->total_remaining_product = $productAttr[0]->payload_value;
    }

    return $result;
  }

  public function getTotalAppliedRateByParams($column, $value){
    $result = BatchProduct::where($column, '=', $value)->sum('applied_rate');
    return floatval($result);
  }

  public function getTotalAppliedRateByParamsByAttribute($productId, $productAttributeId, $merchantId){
    $result = BatchProduct::where('product_id', '=', $productId)->where('product_attribute_id', '=', $productAttributeId)->where('merchant_id', '=', $merchantId)->sum('applied_rate');
    return floatval($result);
  }

  public function getTotalAppliedRateBySpecifiedParams($productId, $merchantId){
    $result = BatchProduct::where('product_id', '=', $productId)->where('merchant_id', '=', $merchantId)->sum('applied_rate');
    return floatval($result); 
  }

  public function getByParams($column, $value, $return){
    return BatchProduct::where($column, '=', $value)->get($return);
  }

  public function getProductInfoByBatch($column, $value){
    $result = BatchProduct::where($column, '=', $value)->groupBy('product_attribute_id')->get(['product_id', 'applied_rate', 'product_attribute_id']);
    $totalApplied = BatchProduct::where($column, '=', $value)->groupBy('product_attribute_id')->sum('applied_rate');
    if(sizeof($result)){
      for ($i=0; $i <= sizeof($result)-1; $i++) { 
        $item = $result[$i];
        $product = app('Increment\Marketplace\Http\ProductController')->retrieveProductWithAttribute($item['product_id'], $item['product_attribute_id']);
        $condition = array(
          array('product_id', '=', $product['id']),
          array('product_attribute_id', '=', $product['attrId'])
        );
        $batchNumber = app('Increment\Marketplace\Http\ProductTraceController')->getTraceByParams($condition, ['batch_number']);
        $result[$i]['product_name'] = $product['title'];
        $result[$i]['batch_number'] = $batchNumber !== null ? $batchNumber['batch_number'] : null;
        $result[$i]['payload'] = $product['payload'];
        $result[$i]['payload_value'] = $product['payload_value'];
        $result[$i]['total_applied_rate'] = floor($totalApplied*100)/100;
      }
    }
    return $result;
  }
}
