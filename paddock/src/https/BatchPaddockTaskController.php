<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\BatchPaddockTask;
use Carbon\Carbon;

class BatchPaddockTaskController extends APIController
{
    //
    function __construct(){
        $this->model = new BatchPaddockTask();
        $this->notRequired = array();
    }    
}
