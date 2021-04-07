<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\ProductAttribute;
class ProductAttributeController extends APIController
{
    function __construct(){
    	$this->model = new ProductAttribute();
    }

    public function getAttribute($id, $payload){
      $result = ProductAttribute::where('product_id', '=', $id)->where('payload', '=', $payload)->get();
      return (sizeof($result) > 0) ? $result : null;
    }

    public function getProductUnit($id){
      $result = ProductAttribute::where('product_id', '=', $id)->select('id', 'payload', 'payload_value')->get();
      return (sizeof($result) > 0) ? $result : null;
    }

    public function getProductUnits($column, $id){
      $result = ProductAttribute::where($column, '=', $id)->select('id', 'payload', 'payload_value')->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['volume'] = $result[$i]['payload_value'].' '.$result[$i]['payload'];
        }
      }
      return (sizeof($result) > 0) ? $result[0]->volume : null;
    }

    public function getProductUnitsByColumns($id){
      $result = ProductAttribute::where('product_id', '=', $id)->select('id', 'payload', 'payload_value')->get();
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function getByParams($column, $value){
      $result = ProductAttribute::where($column, '=', $value)->where('deleted_at', '=', null)->orderBy('created_at', 'desc')->select(['id', 'payload', 'payload_value'])->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['product_trace_qty'] = app('Increment\Marketplace\Http\ProductTraceController')->getTotalAttributeByParams($result[$i]['id']);

          $i++;
        }
      }
      return (sizeof($result) > 0) ? $result : null;
    }

    public function getByParamsWithMerchant($column, $value, $merchantId){
      $result = ProductAttribute::where($column, '=', $value)->where('deleted_at', '=', null)->orderBy('payload_value', 'desc')->select(['id', 'payload', 'payload_value'])->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $productQtyPerVariation = app('Increment\Marketplace\Http\ProductTraceController')->getTotalAttributeByParams($result[$i]['id']);
          $transferredProductQty = app('Increment\Marketplace\Http\TransferredProductController')->getRemainingProductQty($value, $merchantId, $result[$i]['id']);
          $result[$i]['product_trace_qty'] = $productQtyPerVariation - $transferredProductQty;
          $i++;
        }
      }
      return (sizeof($result) > 0) ? $result : null;
    }
}
