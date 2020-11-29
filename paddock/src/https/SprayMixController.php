<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\SprayMix;
use Carbon\Carbon;

class SprayMixController extends APIController
{
    //
    function __construct(){
        $this->model = new SprayMix();
        $this->notRequired = array();
    }    
}
