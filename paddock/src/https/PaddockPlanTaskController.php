<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Carbon\Carbon;

class PaddockPlanTaskController extends APIController
{
    //
    function __construct(){
        $this->model = new PaddockPlanTask();
        $this->notRequired = array();
    }    
}
