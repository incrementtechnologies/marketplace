<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Batch;
use Increment\Marketplace\Paddock\Models\Machine;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Increment\Marketplace\Paddock\Models\BatchPaddockTask;
use Increment\Marketplace\Paddock\Models\BatchProduct;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Carbon\Carbon;

class BatchController extends APIController
{
    public $sprayMixClass = 'Increment\Marketplace\Paddock\Http\SprayMixController';
    public $machineClass = 'Increment\Marketplace\Paddock\Http\MachineController';
    public $batchPaddockTaskClass = 'Increment\Marketplace\Paddock\Http\BatchPaddockTaskController';

    function __construct(){
        // $this->model = new Batch();
        $this->notRequired = array(
            'spray_mix_id','machine_id','notes'
        );
    }

    public function create(Request $request){
      $data = $request->all();
      $batchData = $data['batch'];
      $taskData = $data['tasks'];
      $batchProduct = $data['batch_products'];
      $batch = Batch::create($batchData);
      $batchId = 0;
      $this->response['data']['batch'] = $batch;
      $i = 0;
      foreach ($batchProduct as $key) {
        $batchProduct[$i]['batch_id'] = $this->response['data']['batch']['id'];
        $batchId = $this->response['data']['batch']['id'];
        BatchProduct::create($batchProduct[$i]);
        $i++;
      }
      $j = 0;
      foreach ($data['tasks']['paddock_plan_task_id'] as $key) {
        $taskData['paddock_plan_task_id'] = (int)$key;
        $taskData['batch_id'] = $batchId;
        $taskData['spray_mix_id'] = $batchData['spray_mix_id'];
        $taskData['machine_id'] = $batchData['machine_id'];
        foreach ($data['tasks']['area'] as $key) {
          $taskData['area'] = (int)$key;
        }
        $j++;
        $tasks = BatchPaddockTask::create($taskData);
      };
      $result = Batch::where('id', '=', $this->response['data']['batch']['id'])->get();
      $this->response['data']['batch'] = $result;
      return $this->response();
    }


    public function retrieveUnApplyTasks(Request $request){
      $data = $request->all();
      $result = Batch::where('status', $data['status'])->where('merchant_id', '=', $data['merchant_id'])->get();
      
      $this->response['data'] = $result;

      return $this->response();
    }

    public function update(Request $request){
      $data = $request->all();
      $result = Batch::where('id', '=', $data['id'])->update(array(
        'status' => $data['status'],
        'updated_at' => Carbon::now()
      ));
      $batchTask = app($this->batchPaddockTaskClass)->retrieveByParams('batch_id', $data['id'], ['paddock_plan_task_id']);
      if(sizeOf($batchTask) > 0){
        $i=0;
        foreach ($batchTask as $key => $value) {
          PaddockPlanTask::where('id', '=', $batchTask[$i]['paddock_plan_task_id'])->update(array(
            'status' => $data['status'],
            'updated_at' => Carbon::now(),
          ));
        }
      }

      $this->response['data'] = $result;

      return $this->response();
    }

    public function retrieveApplyTasksRecents(Request $request){
      $data = $request->all();

      $this->response['data'] = array(
        'spray_mixes' => app($this->sprayMixClass)->getByMerchantId($data['merchant_id']),
        'machines'    => app($this->machineClass)->getByMerchantId($data['merchant_id']),
        'recent_spray_mixes' => app($this->sprayMixClass)->getByMerchantId($data['merchant_id']),
        'recent_machines'    => app($this->machineClass)->getByMerchantId($data['merchant_id'])
      );

      return $this->response();
    }
}
