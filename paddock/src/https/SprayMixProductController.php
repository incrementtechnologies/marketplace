<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\SprayMixProduct;
use Carbon\Carbon;

class SprayMixProductController extends APIController
{
    //
    function __construct(){
        $this->model = new SprayMixroduct();
        $this->notRequired = array();
    }    
}
