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
use Increment\Marketplace\Paddock\Models\PaddockPlan;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\Crop;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatchController extends APIController
{
  public $sprayMixClass = 'Increment\Marketplace\Paddock\Http\SprayMixController';
  public $machineClass = 'Increment\Marketplace\Paddock\Http\MachineController';
  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
  public $batchPaddockTaskClass = 'Increment\Marketplace\Paddock\Http\BatchPaddockTaskController';

  function __construct()
  {
    // $this->model = new Batch();
    $this->notRequired = array(
      'spray_mix_id', 'machine_id', 'notes'
    );
  }

  public function create(Request $request)
  {
    $data = $request->all();

    $batchData = $data['batch'];
    $merchant = app($this->merchantClass)->getColumnByParams('id', $batchData['merchant_id'], 'prefix');
    $counter = Batch::where('merchant_id', '=', $batchData['merchant_id'])->count();
    $batchData['session'] = $merchant ? $merchant['prefix'].$this->toCode($counter): $this->toCode($counter);
    $batchData['applied_rate'] = $batchData['application_rate'];

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
      $taskData['paddock_plan_task_id'] = (int)$key['task_id'];
      $taskData['batch_id'] = $batchId;
      $taskData['spray_mix_id'] = $batchData['spray_mix_id'];
      $taskData['machine_id'] = $batchData['machine_id'];
      $taskData['merchant_id'] = $data['tasks']['merchant_id'];
      $taskData['account_id'] =  $data['tasks']['account_id'];
      $taskData['area'] =  $key['area'];
      BatchPaddockTask::create($taskData);
      $j++;
    };
    $result = Batch::where('id', '=', $this->response['data']['batch']['id'])->get();
    $this->response['data']['batch'] = $result;
    return $this->response();
  }

  public function toCode($size){
    $length = strlen((string)$size);
    $code = '00000000';
    return substr_replace($code, $size, intval(7 - $length));
  }


  public function retrieveUnApplyTasks(Request $request)
  {
    $data = $request->all();
    $result = Batch::where('status', $data['status'])->where('merchant_id', '=', $data['merchant_id'])->get();

    $this->response['data'] = $result;

    return $this->response();
  }

  public function update(Request $request)
  {
    $data = $request->all();
    $batchTask = app($this->batchPaddockTaskClass)->retrieveByParams('batch_id', $data['id'], ['paddock_plan_task_id']);
    if (sizeOf($batchTask) > 0) {
      $i = 0;
      foreach ($batchTask as $key => $value) {
        $task = PaddockPlanTask::where('id', '=', $batchTask[$i]['paddock_plan_task_id'])->get();
        $paddock = Paddock::where('id', '=', $task[0]['paddock_id'])->get(['spray_area']);
        $paddockArea = sizeof($paddock) > 0 ? $paddock[0]['spray_area'] : 0;
        $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($batchTask[$i]['paddock_plan_task_id']);
        $result = Batch::where('id', '=', $data['id'])->update(array(
          'status' => (doubleval($paddockArea) - $totalBatchArea) > 0 ? 'partially_completed' : $data['status'],
          'updated_at' => Carbon::now()
        ));
        PaddockPlanTask::where('id', '=', $batchTask[$i]['paddock_plan_task_id'])->update(array(
          'status' => (doubleval($paddockArea) - $totalBatchArea) > 0 ? 'partially_completed' : $data['status'],
          'updated_at' => Carbon::now(),
        ));
      }
    }

    $this->response['data'] = $result;

    return $this->response();
  }

  public function retrieveApplyTasksRecents(Request $request)
  {
    $data = $request->all();

    $this->response['data'] = array(
      'spray_mixes' => app($this->sprayMixClass)->getByMerchantId($data['merchant_id']),
      'machines'    => app($this->machineClass)->getByMerchantId($data['merchant_id']),
      'recent_spray_mixes' => app($this->sprayMixClass)->getByMerchantId($data['merchant_id']),
      'recent_machines'    => app($this->machineClass)->getByMerchantId($data['merchant_id'])
    );

    return $this->response();
  }

  public function retrieveAppliedTask($paddock_plan_task_id)
  {
    $result = DB::table('batches as T1')
      ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
      ->where('T2.paddock_plan_task_id', '=', $paddock_plan_task_id)
      ->where('T1.deleted_at', '=', null)
      ->get();
    if (sizeof($result) > 0) {
      $result = json_decode(json_encode($result), true);
      $i = 0;
      foreach ($result as $key) {
        $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($key['paddock_plan_task_id']);
        $task = PaddockPlanTask::where('id', '=', $key['paddock_plan_task_id'])->get();
        if(sizeof($task) > 0) {
          $paddocks = Paddock::where('id', $task[0]['paddock_id'])->get();
        }
        $remaining = $totalBatchArea != null ? ((double)$paddocks[0]['spray_area'] - (double)$totalBatchArea) : $paddocks[0]['spray_area'];
        $result[$i]['spray_area'] = sizeof($paddocks) > 0 ? $paddocks[0]['spray_area'] : null;
        $result[$i]['total_batch']  = $totalBatchArea != null ? $totalBatchArea : null;
        $result[$i]['remaining_spray_area'] = $remaining <= 0 ? 0 : $remaining;
        if ($key['updated_at'] !== null) {
          $result[$i]['date'] = Carbon::createFromFormat('Y-m-d H:i:s', $key['updated_at'])->copy()->tz($this->response['timezone'])->format('d M');
        } else {
          $result[$i]['date'] = Carbon::createFromFormat('Y-m-d H:i:s', $key['created_at'])->copy()->tz($this->response['timezone'])->format('d M');
        }
        $i++;
      }
      return json_decode(json_encode($result), true);
    } else {
      return [];
    }
  }

  public function retrieveBySession(Request $request)
  {
    $data = $request->all();
    $result = DB::table('batches as T1')
      ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')->where('session', '=', $data['session'])->groupBy('T1.id')->get();
    if (sizeof($result) > 0) {
      $result = json_decode(json_encode($result), true);
      $i = 0;
      foreach ($result as $key) {
        $task = PaddockPlanTask::where('id', '=', $key['paddock_plan_task_id'])->get();
        $paddockPlan = PaddockPlan::select()->where("paddock_id", "=",  $task[0]['paddock_id'])->orderBy('start_date','desc')->limit(1)->get();
      }
    }
  }
}
