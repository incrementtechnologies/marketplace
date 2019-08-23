<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\ProductTrace;
class ProductTraceController extends APIController
{
    function __construct(){
    	$this->model = new ProductTrace();
    }

    public function getByParams($column, $value){
      $result  = ProductTrace::where($column, '=', $value)->orderBy('created_at', 'desc')->limit(5)->get();
      return sizeof($result) > 0 ? $result : null;
    }

    public function getBalanceQty($column, $value){
      $result  = ProductTrace::where($column, '=', $value)->where('status', '=', 'open')->count();
      return $result;
    }
}
