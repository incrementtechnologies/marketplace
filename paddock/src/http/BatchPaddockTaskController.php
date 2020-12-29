<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\BatchPaddockTask;
use Increment\Marketplace\Paddock\Models\Batch;
use Carbon\Carbon;

class BatchPaddockTaskController extends APIController
{
    //
    function __construct(){
        $this->model = new BatchPaddockTask();
        $this->notRequired = array();
    }
    
    public function retrieveBatchByPaddockPlanTask($paddockPlanTaskId){
        $result = BatchPaddockTask::where('paddock_plan_task_id', '=', $paddockPlanTaskId)->get();
        if(sizeof($result) > 0){
            $i++;
            $response = null;
            foreach ($result as $key) {
               $response = Batch::where('id', '=', $key[$i]['id']);
               $i++;
            }
            return $response;
        }else{
            return null;
        }
    }
}
