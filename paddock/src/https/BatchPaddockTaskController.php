<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\BatchPaddockTask;
use Carbon\Carbon;

class BatchPaddockTaskController extends APIController
{
    //
    function __construct(){
        $this->model = new BatchPaddockTask();
        $this->notRequired = array();
    }    
}
