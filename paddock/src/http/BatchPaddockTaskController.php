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
            $response = [];
            foreach ($result as $key) {
               $response = Batch::where('id', '=', $result[$i]['batch_id'])->get();
               $i++;
            }
            return sizeof($response) > 0 ? $response[0] : null;
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

    public function retrieveTotalAreaByBatch($batchId){
        return BatchPaddockTask::where('batch_id', '=', $batchId)->sum('area');
    }

    public function retrieveBatchWithPaddock($column, $value){
        return BatchPaddockTask::leftJoin('batches as T1', 'T1.id', '=', 'batch_paddock_tasks.batch_id')
            ->where($column, '=',$value)->first();
    }

    public function checkIfInProgress($column, $value){
        $inprogress = BatchPaddockTask::leftJoin('batches as T1', 'T1.id', '=', 'batch_paddock_tasks.batch_id')
            ->where($column, '=',$value)->where('T1.status', '=', 'inprogress')->get();
        $completed = BatchPaddockTask::leftJoin('batches as T1', 'T1.id', '=', 'batch_paddock_tasks.batch_id')
            ->where($column, '=',$value)->where('T1.status', '=', 'completed')->get();

        if(sizeof($inprogress) > 0 && sizeof($completed) <= 0){
            return 'inprogress';
        }else if(sizeof($inprogress) > 0 && sizeof($completed) > 0){
            return 'partially_completed';
        }else if(sizeof($inprogress) <= 0 && sizeof($completed) > 0){
            return 'completed';
        }else{
            return 'pedning';
        }
    }

}
