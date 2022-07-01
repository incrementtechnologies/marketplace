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
  public $batchProductClass = 'Increment\Marketplace\Paddock\Http\BatchProductController';

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
    $batchData['session'] = $merchant ? $merchant['prefix'] . $this->toCode($counter) : $this->toCode($counter);
    $batchData['applied_rate'] = $batchData['application_rate'];
    $batchData['status'] = sizeof($data['tasks']) > 0 ? 'inprogress' : $batchData['status'];
    $batchData['created_at'] = Carbon::now();
    $batchProduct = $data['batch_products'];
    $batch = Batch::create($batchData);
    $this->response['data']['batch'] = $batch;
    $batchId = $this->response['data']['batch']['id'];
    $i = 0;
    foreach ($batchProduct as $key) {
      $batchProduct[$i]['batch_id'] = $this->response['data']['batch']['id'];
      $batchId = $this->response['data']['batch']['id'];
      $batchProduct[$i]['batch_id'] = $batchId;
      if($batchData['spray_mix_id'] != null){
        $batchProduct[$i]['applied_rate'] = $key['used_rate'];
      }else{
        $batchData['applied_rate'] = $batchData['applied_rate'];
      }
      BatchProduct::create($batchProduct[$i]);
      $i++;
    }
    if(sizeof($data['tasks']) > 0){
      $j = 0;
      foreach ($data['tasks']['paddock_plan_task_id'] as $key) {
        PaddockPlanTask::where('id', '=', $key['task_id'])->update(array(
          'status' =>  'inprogress',
          'updated_at' => Carbon::now(),
        ));
        $exist = $this->checkIfExist($batchData['spray_mix_id'], (int)$key['task_id']);
        $taskData['batch_id'] = $batchId;
        $taskData['status'] = 'inprogress';
        $taskData['paddock_plan_task_id'] = (int)$key['task_id'];
        $taskData['spray_mix_id'] = $batchData['spray_mix_id'];
        $taskData['machine_id'] = $batchData['machine_id'];
        $taskData['merchant_id'] = $data['tasks']['merchant_id'];
        $taskData['account_id'] =  $batchProduct[0]['account_id'];
        $taskData['area'] =  $key['area'];
        BatchPaddockTask::create($taskData);
        $j++;
      };
    }
    $result = Batch::where('id', '=', $this->response['data']['batch']['id'])->get();
    $result[0]['created_at'] = Carbon::parse($result[0]['created_at'])->setTimezone('GMT+8');
    $result[0]['updated_at'] = Carbon::parse($result[0]['updated_at'])->setTimezone('GMT+8');
    $this->response['data']['batch'] = $result;
    return $this->response();
  }

  public function toCode($size)
  {
    $length = strlen((string)$size);
    $code = '00000000';
    return substr_replace($code, $size, intval(7 - $length));
  }


  public function retrieveUnApplyTasks(Request $request)
  {
    $data = $request->all();
    $result = Batch::leftJoin('batch_paddock_tasks as T3', 'T3.batch_id', '=', 'batches.id')
      ->leftJoin('paddock_plans_tasks as T2', 'T2.id', '=', 'T3.paddock_plan_task_id')->where(function ($query) {
        $query->where('batches.status', '=', 'inprogress');
      })->where('T2.merchant_id', '=', $data['merchant_id'])->select('batches.id as id')->get();

    $this->response['data'] = $result;

    return $this->response();
  }

  public function update(Request $request)
  {
    $data = $request->all();
    $batchTask = app($this->batchPaddockTaskClass)->retrieveByParams('batch_id', $data['id'], ['paddock_plan_task_id']);
    if (sizeOf($batchTask) > 0) {
      for ($i=0; $i <= sizeof($batchTask)-1 ; $i++) { 
        $item = $batchTask[$i];
        $task = PaddockPlanTask::where('id', '=', $item['paddock_plan_task_id'])->first();
        $paddock = Paddock::where('id', '=', $task['paddock_id'])->select('spray_area')->first();
        $paddockArea = $paddock !== null ? $paddock['spray_area'] : 0;
        $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($item['paddock_plan_task_id']);
  
        PaddockPlanTask::where('id', '=', $item['paddock_plan_task_id'])->update(array(
          'status' => ((float)$paddockArea - (float)$totalBatchArea) == 0 ? 'completed' : 'inprogress',
          'updated_at' => Carbon::now(),
        ));
        $result = Batch::where('id', '=', $data['id'])->update(array(
          'status' =>  'completed',
          'updated_at' => Carbon::now()
        ));
      }
    }
    $this->response['data'] = $result;
    return $this->response();
  }

  public function checkIfHasRemainingArea($tasks)
  {
    $i = 0;
    $counter = 0;
    foreach ($tasks as $key) {
      $task = PaddockPlanTask::where('id', '=', $key['task_id'])->get();
      $paddock = Paddock::where('id', '=', $task[0]['paddock_id'])->get(['spray_area']);
      $paddockArea = sizeof($paddock) > 0 ? $paddock[0]['spray_area'] : 0;
      $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($key['task_id']);
      if ((doubleval($paddockArea) - ($totalBatchArea + $key['area'])) > 0) {
        $counter++;
      }
      $i++;
    }
    return $counter;
  }

  public function checkIfExist($sprayMix, $taskId)
  {
    return BatchPaddockTask::where('spray_mix_id', '=', $sprayMix)->where('paddock_plan_task_id', '=', $taskId)->first();
  }

  public function checkIfExistBatch($tasks, $sprayMix)
  {
    $i = 0;
    $batchId = 0;
    foreach ($tasks as $key) {
      $task = BatchPaddockTask::where('spray_mix_id', '=', $sprayMix)->where('paddock_plan_task_id', '=', $key['task_id'])->get(['batch_id']);
      $batchId = sizeof($task) > 0 ? $task[0]['batch_id'] : 0;
      $i++;
    }
    $exist = Batch::where('id', '=', $batchId)->get();
    return sizeof($exist) > 0 ? $exist : [];
  }

  public function retrieveApplyTasksRecents(Request $request)
  {
    $data = $request->all();
    $tempMix = Batch::where('merchant_id', '=', $data['merchant_id'])->groupBy('spray_mix_id')->orderBy('updated_at')->limit(3)->get();
    $tempMac = Batch::where('merchant_id', '=', $data['merchant_id'])->groupBy('machine_id')->orderBy('updated_at')->limit(3)->get();
    if(sizeof($tempMix) > 0 && sizeof($tempMac) > 0){
      $tempSpray = array();
      $tempMachine = array();
      for ($i=0; $i <= sizeof($tempMix)-1; $i++) { 
        $item = $tempMix[$i];
        $recentSpray = app($this->sprayMixClass)->getByParams('id', $item['spray_mix_id'], ['name', 'id', 'maximum_rate', 'minimum_rate', 'application_rate']);
        if($recentSpray !== null){
          array_push($tempSpray, $recentSpray);
        }
      }
      for ($i=0; $i <= sizeof($tempMac)-1; $i++) { 
        $item = $tempMac[$i];
        $recentMachine = app($this->machineClass)->getMachineByParams('id', $item['machine_id']);
        if($recentMachine !== null){
          array_push($tempMachine, $recentMachine);
        }
      }
      $this->response['data'] = array(
        'spray_mixes' => app($this->sprayMixClass)->getByMerchantId($data['merchant_id']),
        'machines'    => app($this->machineClass)->getByMerchantId($data['merchant_id']),
        'recent_spray_mixes' => $tempSpray,
        'recent_machines'    => $tempMachine
      );
    }else{
      $this->response['data'] = array(
        'spray_mixes' => app($this->sprayMixClass)->getByMerchantId($data['merchant_id']),
        'machines'    => app($this->machineClass)->getByMerchantId($data['merchant_id']),
        'recent_spray_mixes' => app($this->sprayMixClass)->getByMerchantId($data['merchant_id']),
        'recent_machines'    => app($this->machineClass)->getByMerchantId($data['merchant_id'])
      );
    }

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
        if (sizeof($task) > 0) {
          $paddocks = Paddock::where('id', $task[0]['paddock_id'])->get();
          $result[$i]['status'] = $task[0]['status'];
        }
        $result[$i]['status'] = $key['status'];
        $remaining = $totalBatchArea != null ? (float)$totalBatchArea : $paddocks[0]['spray_area'];
        $remaining = $totalBatchArea != null ? ((float)$paddocks[0]['spray_area'] - (float)$totalBatchArea) : $paddocks[0]['spray_area'];
        $result[$i]['spray_area'] = sizeof($paddocks) > 0 ? $paddocks[0]['spray_area'] : null;
        $result[$i]['machine'] =  app($this->machineClass)->getMachineNameByParams('id', $key['machine_id']);
        $result[$i]['total_batch']  = $totalBatchArea != null ? $totalBatchArea : null;
        $result[$i]['remaining_spray_area'] = $remaining <= 0 ? 0 :  $this->numberConvention($remaining);
        $result[$i]['total_batch_area'] = app($this->batchPaddockTaskClass)->retrieveTotalAreaByBatch($key['batch_id']);
        if ($key['updated_at'] !== null) {
          // $temp = Carbon::createFromFormat('Y-m-d H:i:s', $key['updated_at'])->copy()->tz($this->response['timezone'])->format('d M');
          // dd($temp);
          $result[$i]['date'] = Carbon::parse($key['updated_at'])->format('d M');
        } else {
          $result[$i]['date'] = Carbon::parse($key['created_at'])->format('d M');
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
        $paddockPlan = PaddockPlan::select()->where("paddock_id", "=",  $task[0]['paddock_id'])->orderBy('start_date', 'desc')->limit(1)->get();
      }
    }
  }

  public function retrieveSessions(Request $request){
    $data = $request->all();
    $con = $data['condition'];
    $result = Batch::leftJoin('batch_paddock_tasks as T1', 'T1.batch_id', '=', 'batches.id')
        ->leftJoin('accounts as T2', 'T2.id', '=', 'batches.account_id')
        ->leftJoin('account_informations as T3', 'T3.account_id', '=', 'T2.id')
        ->where('T1.id', '=', null)
        ->where('batches.status', '=', 'completed')
        ->where(function($query)use($con){
            $query->where('batches.session', 'like', $con[0]['value'])
            ->orWhere('T3.first_name', 'like', $con[0]['value'])
            ->orWhere('T3.last_name', 'like', $con[0]['value']);
        })
        ->limit($data['limit'])
        ->offset($data['offset'])
        ->orderBy('batches.created_at', 'desc')
        ->get(['batches.*', 'T2.username', 'T3.first_name', 'T3.last_name']);
    $size = Batch::leftJoin('batch_paddock_tasks as T1', 'T1.batch_id', '=', 'batches.id')
    ->leftJoin('accounts as T2', 'T2.id', '=', 'batches.account_id')
    ->leftJoin('account_informations as T3', 'T3.account_id', '=', 'T2.id')
    ->where('T1.id', '=', null)
    ->where('batches.status', '=', 'completed')
    ->get();
    $res = array();
    if(sizeof($result) > 0){
      for ($i=0; $i <= sizeof($result)-1 ; $i++) {
        $item = $result[$i];
        $result[$i]['name'] = $item['first_name'].' '.$item['last_name'];
        $result[$i]['date_completed_formatted'] = Carbon::createFromFormat('Y-m-d H:i:s', $item['updated_at'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
        // if($con[0]['value'] !== null){
        //   if(str_contains(strtolower($result[$i]['name']), strtolower($con[0]['value'])) ||  str_contains(strtolower($item['session']), strtolower($con[0]['value']))){
        //     array_push($res, $item);
        //   }
        // }else{
        //   array_push($res, $item);
        // }
      }
      $this->response['data'] = $result;
      $this->response['size'] = sizeof($size);
    }
    return $this->response();
  }

  public function retriveBatchBySession(Request $request){
    $data = $request->all();
    $result = Batch::where('session', '=', $data['session'])->first();
    if($result !== null){
      $result['date_completed_formatted'] = Carbon::createFromFormat('Y-m-d H:i:s', $result['updated_at'])->copy()->tz($this->response['timezone'])->format('d/m/Y H:i');
      $result['products'] = app($this->batchProductClass)->getProductInfoByBatch('batch_id', $result['id']);
      $result['operator'] = $this->retrieveName($result['account_id']);
    }
    $this->response['data'] = $result;
    return $this->response();
  }
}
