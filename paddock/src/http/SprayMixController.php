<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Carbon\Carbon;

class SprayMixController extends APIController
{
    //
    function __construct(){
        $this->model = new SprayMix();
        $this->notRequired = array();
    }    
}
