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
}
