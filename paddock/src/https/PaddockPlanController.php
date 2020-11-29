<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\PaddockPlan;
use Carbon\Carbon;

class PaddockPlanController extends APIController
{
    //
    function __construct(){
        $this->model = new PaddockPlan();
        $this->notRequired = array();
    }    
}
