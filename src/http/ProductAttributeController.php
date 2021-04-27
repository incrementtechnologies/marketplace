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
          $unit = $this->convertUnits($result[$i]['payload']);
          $result[$i]['volume'] = $this->convertVariation($result[$i]['payload'], $result[$i]['payload_value']);
        }
      }
      return (sizeof($result) > 0) ? $result[0]->volume : null;
    }

    public function getAttributeByParams($column, $id){
      $result = ProductAttribute::where($column, '=', $id)->select('id', 'payload', 'payload_value')->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['payload'] = $this->convertUnits($result[$i]['payload']);
        }
      }
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function convertUnits($payload){
      switch($payload){
        case 'Liters (L)': return 'L';
        case 'Litres (L)': return 'L';
        case 'Milliliters (ml)': return 'ml';
        case 'Millilitres (ml)': return 'ml';
        case 'Kilograms (kg)': return 'kg';
        case 'Grams (g)': return 'g';
        case 'Milligrams (mg)': return 'mg';
      }
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
          $result[$i]['unit'] = $this->convertUnits($result[$i]['payload']);
          $result[$i]['product_trace_qty'] = app('Increment\Marketplace\Http\ProductTraceController')->getTotalAttributeByParams($result[$i]['id']);

          $i++;
        }
      }
      return (sizeof($result) > 0) ? $result : null;
    }

    public function getByParamsWithMerchant($column, $value, $merchantId){
      $result = ProductAttribute::where($column, '=', $value)->where('deleted_at', '=', null)->orderBy('payload_value', 'asc')->select(['id', 'payload', 'payload_value'])->get();
      $finalResult = array();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $exist = app('Increment\Marketplace\Http\BundledSettingController')->getByParamsDetails('product_attribute_id', $result[$i]['id']);
          $result[$i]['payload_value'] = (int)$result[$i]['payload_value'];
          $productQtyPerVariation = app('Increment\Marketplace\Http\ProductTraceController')->getTotalAttributeByParams($result[$i]['id']);
          $transferredProductQty = app('Increment\Marketplace\Http\TransferredProductController')->getTransferredProductInManufacturer($value, $result[$i]['id']);
          $result[$i]['product_trace_qty'] = $productQtyPerVariation - $transferredProductQty;
          $result[$i]['is_used'] = $exist !== null ? true : false;
          $i++;
        }
      }
      return $result;
    }

    public function getByParamsSortedCreatedAt($column, $value, $merchantId){
      $result = ProductAttribute::where($column, '=', $value)->where('deleted_at', '=', null)->orderBy('created_at', 'asc')->select(['id', 'payload', 'payload_value'])->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['payload_value'] = (int)$result[$i]['payload_value'];
          $productQtyPerVariation = app('Increment\Marketplace\Http\ProductTraceController')->getTotalAttributeByParams($result[$i]['id']);
          $transferredProductQty = app('Increment\Marketplace\Http\TransferredProductController')->getRemainingProductQty($value, $merchantId, $result[$i]['id']);
          $result[$i]['product_trace_qty'] = $productQtyPerVariation - $transferredProductQty;
          $i++;
        }
      }
      return $result;
    }

    public function convertVariation($payload, $payloadValue){
      if((float)$payloadValue % 10000 == 0){
        $result = null;
        switch($payload){
          case 'Liters (L)': 
            $result = (float)$payloadValue/1000;
            return $result.' m3';
          case 'Litres (L)': 
            $result = (float)$payloadValue/1000;
            return $result.' m3';
          case 'Milliliters (ml)': 
            $result = (float)$payloadValue/1000;
            return $result.' L';
          case 'Millilitres (ml)': 
            $result = (float)$payloadValue/1000;
            return $result.' L';
          case 'Kilograms (kg)': 
            $result = (float)$payloadValue/1000;
            return $result.' tonne';
          case 'Grams (g)': 
            $result = (float)$payloadValue/1000;
            return $result.' kg';
          case 'Milligrams (mg)': 
            $result = (float)$payloadValue/1000;
            return $result.' mg';
        }
      }else{
        $unit = $this->convertUnits($payload);
        return $payloadValue.' '.$unit;
      }
    }
}
