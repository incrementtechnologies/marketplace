<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\BatchProduct;
use Carbon\Carbon;

class BatchProductController extends APIController
{
    //
    function __construct(){
        $this->model = new BatchProduct();
        $this->notRequired = array();
    }
    
    public function getProductQtyTrace($merchantId, $column, $value){
        $result = BatchProduct::where($column, '=', $value)->where('merchant_id', '=', $merchantId)->count();

        return $result;
    }
}
