<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\PaddockPlanTask;
use Carbon\Carbon;

class PaddockPlanTaskController extends APIController
{
    //
    function __construct(){
        $this->model = new PaddockPlanTask();
        $this->notRequired = array();
    }    
}
