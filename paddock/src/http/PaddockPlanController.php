<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlan;
use Carbon\Carbon;

class PaddockPlanController extends APIController
{
    //
    function __construct(){
        $this->model = new PaddockPlan();
        $this->notRequired = array();
    }    
}
