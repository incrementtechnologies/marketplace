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

    public function getByParams($column, $value){
      $result = ProductAttribute::where($column, '=', $value)->orderBy('created_at', 'desc')->get();
      return (sizeof($result) > 0) ? $result : null;
    }
}
