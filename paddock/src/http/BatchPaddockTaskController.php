<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\BatchPaddockTask;
use Increment\Marketplace\Paddock\Models\Batch;
use Carbon\Carbon;

class BatchPaddockTaskController extends APIController
{
    public $machineClass = 'Increment\Marketplace\Paddock\Http\MachineController';
    //
    function __construct(){
        $this->model = new BatchPaddockTask();
        $this->notRequired = array();
    }
    
    public function retrieveBatchByPaddockPlanTask($paddockPlanTaskId){
        $result = BatchPaddockTask::where('paddock_plan_task_id', '=', $paddockPlanTaskId)->get();
        if(sizeof($result) > 0){
            $i = 0;
            $response = null;
            foreach ($result as $key) {
               $response = Batch::where('id', '=', $result[$i]['id'])->get();
               $i++;
            }
            return $response[0];
        }else{
            return null;
        }
    }

    public function getMachinedByBatches($column, $value){
        $result = BatchPaddockTask::with('batches')->where($column, '=', $value)->get();
        $machine = sizeof($result) > 0 ? app($this->machineClass)->getMachineNameByParams('id', $result[0]['machine_id']) : null;
        return $machine != null ? $machine[0]['name'] : null;
    }

    public function retrieveByParams($column, $value, $return){
        $result = BatchPaddockTask::where($column, '=', $value)->get($return);
        return $result;
    }

    public function getTotalBatchPaddockPlanTask($paddockPlanTaskId){
        return BatchPaddockTask::where('paddock_plan_task_id', '=', $paddockPlanTaskId)->sum('area');
    }
}
