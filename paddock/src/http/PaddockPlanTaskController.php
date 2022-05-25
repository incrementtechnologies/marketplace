<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Increment\Marketplace\Models\OrderRequest;
use Increment\Marketplace\Paddock\Models\Batch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaddockPlanTaskController extends APIController
{

    public $paddockClass = 'Increment\Marketplace\Paddock\Http\PaddockController';
    public $cropClass = 'Increment\Marketplace\Paddock\Http\CropController';
    public $machineClass = 'Increment\Marketplace\Paddock\Http\MachineController';
    public $sprayMixClass = 'Increment\Marketplace\Paddock\Http\SprayMixController';
    public $paddockPlanClass = 'Increment\Marketplace\Paddock\Http\PaddockPlanController';
    public $batchPaddockTaskClass = 'Increment\Marketplace\Paddock\Http\BatchPaddockTaskController';
    public $orderRequestClass = 'Increment\Marketplace\Http\OrderRequestController';


    function __construct()
    {
        $this->model = new PaddockPlanTask();
        $this->notRequired = array();
    }

    public function retrieve(Request $request)
    {
        $data = $request->all();
        $this->model = new PaddockPlanTask();
        $this->retrieveDB($data);
        for ($i = 0; $i < count($this->response['data']); $i++) {
            $spraymixdata = SprayMix::select('name')->where('id', '=', $this->response['data'][$i]['spray_mix_id'])->get();
            if (count($spraymixdata) != 0) {
                $this->response['data'][$i]['spray_mix_name'] = $spraymixdata[0]['name'];
            }
            $this->response['data'][$i]['due_date'] = Carbon::createFromFormat('Y-m-d', $this->response['data'][$i]['due_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
        }
        return $this->response();
    }

    public function retrieveTaskByPaddock($paddockPlanId)
    {
        $result = PaddockPlanTask::where('paddock_plan_id', '=', $paddockPlanId)->get(['spray_mix_id', 'id', 'paddock_plan_id', 'due_date']);
        if (sizeof($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }

    public function retrieveMobileDueTask(Request $request)
    {
        $data = $request->all();
        $con = $data['condition'];
        $result = [];
        $result = Paddock::where($con[0]['column'], '=', $con[0]['value'])->get();
        $currDate = Carbon::now()->toDateString();
        $finalResult = array();
        $size = Paddock::where($con[0]['column'], '=', $con[0]['value'])->get();
        $counter = 0;
        if(sizeof($size) > 0){
            for ($a=0; $a <= sizeof($size)-1; $a++) {
                $item = $size[$a];
                $task = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])
                ->where('paddock_id', '=', $item['id'])
                // ->where('status', '=', 'approved')
                ->orderBy('due_date', 'asc')
                ->first();
                if ($task != null && ($task['status'] !== 'pending' || $task['status'] !== 'completed')) {
                    $batchPaddock = app($this->batchPaddockTaskClass)->retrieveByParams('paddock_plan_task_id', $task['id'], ['batch_id']);
                    $batchStatus = sizeof($batchPaddock) > 0 ? Batch::where('id', '=', $batchPaddock[0]['batch_id'])->first() : null;
                    $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($task['id']);
                    $area = (float)$item['area'];
                    $totalArea =  $totalBatchArea != null ? ((float)$item['spray_area'] - (float)$totalBatchArea) : (float)$item['spray_area'];
                    $remainingSprayArea = $this->numberConvention($totalArea);
                    if ($remainingSprayArea > 0) {
                        if($batchStatus !== null){
                            if($batchStatus !== 'completed'){
                                $counter ++;
                            }
                        }else{
                            $counter ++;
                        }
                    }
                }
            }
        }
        if (sizeof($result) > 0) {
            $result = json_decode(json_encode($result), true);
            $i = 0;
            foreach ($result as $key) {
                $tempDates = [];
                $task = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])
                    ->where('paddock_id', '=', $key['id'])
                    ->orderBy('due_date', 'asc')
                    ->get();
                if(sizeof($task) > 0){
                    for ($a=0; $a <= sizeof($task)-1; $a++) {
                        $each = $task[$a];
                        array_push($tempDates, $each['due_date']);
                    }
                    foreach ($tempDates as $date) {
                        $params = array(
                            array($con[0]['column'], '=', $con[0]['value']),
                            array('paddock_id', '=', $key['id']),
                            array('due_date', '=', $date),
                        );
                        $remainingSpray = $this->getRemainingSprayArea($params, $key);
                        if($remainingSpray !== null && $remainingSpray['remaining_spray_area'] > 0){
                            $taskId = $remainingSpray['id'];
                            $paddockPlan = app($this->paddockPlanClass)->retrievePlanByParams('id', $remainingSpray['paddock_plan_id'], ['start_date', 'end_date', 'crop_id', 'paddock_id']);
                            $batchPaddock = app($this->batchPaddockTaskClass)->retrieveByParams('paddock_plan_task_id', $taskId, ['batch_id']);
                            $batchStatus = sizeof($batchPaddock) > 0 ? Batch::where('id', '=', $batchPaddock[0]['batch_id'])->first() : null;
                            $result[$i]['area'] = (float)$key['area'];
                            $result[$i]['due_date'] = $remainingSpray['due_date'];
                            $result[$i]['category'] = $this->retrieveByParams('id', $taskId, 'category');
                            $result[$i]['id'] =  $taskId;
                            $result[$i]['task_status'] = $remainingSpray['status'];
                            $result[$i]['batch_status'] = $batchStatus != null ? $batchStatus['status'] : null;
                            $result[$i]['paddock_plan_task_id'] =  $taskId;
                            $result[$i]['nickname'] = $this->retrieveByParams('id', $taskId, 'nickname');
                            $result[$i]['machine'] = app($this->batchPaddockTaskClass)->getMachinedByBatches('paddock_plan_task_id', $taskId);
                            $result[$i]['spray_mix_id'] = $this->retrieveByParams('id', $taskId, 'spray_mix_id');
                            $result[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $remainingSpray['spray_mix_id'], ['id', 'name']);
                            $result[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $taskId, 'paddock_plan_id');
                            $result[$i]['paddock_id'] = $this->retrieveByParams('id', $taskId, 'paddock_id');
                            $result[$i]['paddock'] = array(
                                'name' => $key['name'],
                                'spray_area' => $key['spray_area'],
                                'id' => $key['id']
                            );
                            if (isset($temp[$i]['paddock']['crop_name'])) {
                                $temp[$i]['paddock']['crop_name'] = app($this->cropClass)->retrieveCropById($paddockPlan[0]['crop_id'])[0]->name;
                            }
                            array_push($finalResult, $result[$i]);
                            break;
                        }
                    }
                    
                }
                // if ($task != null && $task['status'] !== 'pending') {
                //     $taskId = $task['id'];
                //     $paddockPlan = app($this->paddockPlanClass)->retrievePlanByParams('id', $task['paddock_plan_id'], ['start_date', 'end_date', 'crop_id', 'paddock_id']);
                //     $batchPaddock = app($this->batchPaddockTaskClass)->retrieveByParams('paddock_plan_task_id', $taskId, ['batch_id']);
                //     $batchStatus = sizeof($batchPaddock) > 0 ? Batch::where('id', '=', $batchPaddock[0]['batch_id'])->first() : null;
                //     $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($taskId);
                //     $result[$i]['area'] = (float)$key['area'];
                //     $totalArea =  $totalBatchArea != null ? ((float)$key['spray_area'] - (float)$totalBatchArea) : (float)$key['spray_area'];
                //     $result[$i]['remaining_spray_area'] = $this->numberConvention($totalArea);
                    
                //     if ($result[$i]['remaining_spray_area'] > 0) {
                //         if($batchStatus !== null){
                //             if($batchStatus !== 'completed'){
                //                 array_push($finalResult, $result[$i]);
                //             }
                //         }else{
                //             array_push($finalResult, $result[$i]);
                //         }
                //     }
                //     // }
                // }
                $i++;
            }
            $fin = array();
            $finalResult = collect($finalResult);
            $finalResult = $finalResult->sortBy('due_date');
            $finalResult =  json_decode(json_encode($finalResult), true);
            $finalResult = array_values($finalResult);
            for ($a=0; $a <= sizeof($finalResult)-1; $a++) { 
                $items = $finalResult[$a];
                $finalResult[$a]['due_date'] = Carbon::createFromFormat('Y-m-d', $items['due_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
            }
            $finalResult = array_slice($finalResult, $data['offset'], $data['limit']);
            $this->response['data'] = $finalResult;
            $this->response['size'] = $counter;
            return $this->response();
        }
    }

    public function arraySort($data){
        $data->map(function($item){
            $item['due_date'] = Carbon::createFromFormat('Y-m-d', $item['due_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
            return $item;
        });
        return $data;
    }

    public function getRemainingSprayArea($params, $key){
        $task = PaddockPlanTask::where($params)->orderBy('due_date', 'asc')->first();
        if($task != null && $task['status'] !== 'pending'){
            $taskId = $task['id'];
            $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($taskId);
            $totalArea =  $totalBatchArea != null ? ((float)$key['spray_area'] - (float)$totalBatchArea) : (float)$key['spray_area'];
            return array(
                'remaining_spray_area' => $this->numberConvention($totalArea),
                'due_date' => $task['due_date'],
                'status' => $task['status'],
                'spray_mix_id' => $task['spray_mix_id'],
                'id' => $task['id'],
                'paddock_plan_id' => $task['paddock_plan_id']
            );
        }else{
            return null;
        }
    }

    public function retrieveMobileByParams(Request $request)
    {
        $data = $request->all();
        $con = $data['condition'];
        $result = null;
        if (isset($data['limit'])) {
            $result = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])->where($con[1]['column'], '=', $con[1]['value'])->skip($data['offset'])->orderBy('due_date', 'desc')->take($data['limit'])->get();
        } else {
            $result = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])->where($con[1]['column'], '=', $con[1]['value'])->get();
        }
        $temp = $result;
        $finalResult = array();
        $date =  Carbon::now();
        $currDate = $date->toDateString();
        if (sizeof($temp) > 0) {
            $i = 0;
            $j = 1;
            foreach ($temp as $key) {
                $paddockPlan = app($this->paddockPlanClass)->retrievePlanByParams('id', $key['paddock_plan_id'], ['start_date', 'end_date']);

                if ($paddockPlan[0]['start_date'] <= $currDate && $currDate <= $paddockPlan[0]['end_date']) {
                    $paddocks = app($this->paddockPlanClass)->retrievePlanByParams('id', $key['paddock_plan_id'], ['crop_id', 'paddock_id']);
                    $existInBatch = app($this->batchPaddockTaskClass)->retrieveByParams('paddock_plan_task_id', $temp[$i]['id'], ['id', 'spray_mix_id']);
                    if (sizeof($existInBatch) <= 0) {
                        $temp[$i]['paddock'] = app($this->paddockClass)->getByParams('id', $paddocks[0]['paddock_id'], ['id', 'name', 'spray_area']);
                        if ($temp[$i]['paddock'] !== null) {
                            $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($temp[$i]['id']);
                            $temp[$i]['area'] = (float)$temp[$i]['area'];
                            $totalArea =  $totalBatchArea != null ? ((float)$temp[$i]['paddock']['spray_area'] - (float)$totalBatchArea) : (float)$temp[$i]['paddock']['spray_area'];
                            $temp[$i]['remaining_spray_area'] = $this->numberConvention($totalArea);
                            $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $key['paddock_id'], ['id', 'name']);
                            $temp[$i]['due_date'] = $this->retrieveByParams('id', $temp[$i]['id'], 'due_date');
                            $temp[$i]['category'] = $this->retrieveByParams('id', $temp[$i]['id'], 'category');
                            $temp[$i]['nickname'] = $this->retrieveByParams('id', $temp[$i]['id'], 'nickname');
                            $temp[$i]['machine'] = app($this->batchPaddockTaskClass)->getMachinedByBatches('paddock_plan_task_id', $temp[$i]['id']);
                            $temp[$i]['spray_mix_id'] = $this->retrieveByParams('id', $temp[$i]['id'], 'spray_mix_id');
                            $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $existInBatch[0]['spray_mix_id'], ['id', 'name']);
                            $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $temp[$i]['id'], 'paddock_plan_id');
                            $temp[$i]['paddock_id'] = $this->retrieveByParams('id', $temp[$i]['id'], 'paddock_id');
                            $temp[$i]['paddock_plan_task_id'] = $key['id'];
                            if (isset($temp[$i]['paddock']['crop_name'])) {
                                $temp[$i]['paddock']['crop_name'] = app($this->cropClass)->retrieveCropById($paddocks[0]['crop_id'])[0]->name;
                            }
                            if ($temp[$i]['remaining_spray_area'] > 0) {
                                array_push($finalResult, $temp[$i]);
                            }
                        }
                    }
                }
                $i++;
            }
            $this->response['data'] = $finalResult;
        }
        return $this->response();
    }

    public function retrieveFromBatch(Request $request){
        $data = $request->all();
        $con = $data['condition'];
        if($con[0]['value'] === 'inprogress'){
            $result = Batch::rightJoin('batch_paddock_tasks as T1', 'T1.batch_id', '=', 'batches.id')
                ->where('batches.'.$con[0]['column'], '=', $con[0]['value'])
                ->where('batches.deleted_at', '=', null)
                ->where('status', '=', $con[1]['value'])
                ->skip($data['offset'])->take($data['limit'])->orderBy('batches.created_at', 'desc')->get();
        }else{
            $result = Batch::rightJoin('batch_paddock_tasks as T1', 'T1.batch_id', '=', 'batches.id')
                ->where('batches.'.$con[0]['column'], '=', $con[0]['value'])
                ->where('batches.deleted_at', '=', null)
                ->where('status', '=', $con[1]['value'])
                ->groupBy('T1.paddock_plan_task_id')
                ->skip($data['offset'])->take($data['limit'])->orderBy('batches.created_at', 'desc')->get();
        }

        $final = array();
        if(sizeof($result) > 0){
            $i= 0;
            foreach ($result as $key) {
                $task = PaddockPlanTask::where('id', '=', $key['paddock_plan_task_id'])->orderBy('updated_at', 'desc')->first();
                $result[$i]['paddock'] = app($this->paddockClass)->getByParams('id', $task['paddock_id'], ['id', 'name', 'spray_area']);
                $result[$i]['batch_status'] = $key['status'];
                $result[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $result[$i]['spray_mix_id'], ['id', 'name']);
                if ($con[1]['value'] == 'inprogress') {
                    $result[$i]['due_date'] = Carbon::createFromFormat('Y-m-d',  $task['due_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
                    array_push($final, $result[$i]);
                } else {
                    $lastAdded = app($this->batchPaddockTaskClass)->retrieveLastAdded('paddock_plan_task_id', $key['paddock_plan_task_id']);
                    $result[$i]['due_date'] = Carbon::parse($lastAdded['updated_at'])->format('d/m/Y');
                    $paddockArea = $result[$i]['paddock']['spray_area'];
                    $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($result[$i]['paddock_plan_task_id']);
                    if($key['status'] === $task['status'] && ((float)$paddockArea - $totalBatchArea) <= 0){
                        array_push($final, $result[$i]);
                    }
                }
                $i++;
            }
        }
        $this->response['data'] = $final;
        return $this->response();
    }

    public function retrieveMobileByParamsEndUser(Request $request)
    {
        $data = $request->all();
        $con = $data['condition'];
        if ($con[1]['value'] == 'inprogress') {
            $result = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])
                ->where('deleted_at', '=', null)
                ->where(function ($query) {
                    $query->where('status', '=', 'inprogress');
                })
                ->skip($data['offset'])->take($data['limit'])->orderBy('created_at', 'desc')->get();
        } else {
            $result = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])
                ->where('deleted_at', '=', null)
                ->where($con[1]['column'], '=', $con[1]['value'])
                ->skip($data['offset'])->take($data['limit'])->orderBy('created_at', 'desc')->get();
        }
        $obj = $result;
        if (sizeof($obj) > 0) {
            $i = 0;
            $temp = json_decode(json_encode($obj), true);
            $res = [];
            foreach ($temp as $key) {
                $batchPaddock = app($this->batchPaddockTaskClass)->retrieveByParams('paddock_plan_task_id', $temp[$i]['id'], ['batch_id']);
                $batchStatus = sizeof($batchPaddock) > 0 ? Batch::where('id', '=', $batchPaddock[0]['batch_id'])->first() : null;
                $paddockId = $this->retrieveByParams('id', $temp[$i]['id'], 'paddock_id');
                $temp[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name', 'spray_area']) : null;
                $paddoctId = $this->retrieveByParams('id', $temp[$i]['id'], 'paddock_plan_id');
                $paddockPlanDate = app($this->paddockPlanClass)->retrievePlanByParams('id', $paddoctId, ['start_date', 'end_date']);
                if ($con[1]['value'] == 'inprogress') {
                    $paddockPlanDate[0]['start_date'] = Carbon::createFromFormat('Y-m-d', $paddockPlanDate[0]['start_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
                    $temp[$i]['due_date'] = Carbon::createFromFormat('Y-m-d',  $this->retrieveByParams('id', $temp[$i]['id'], 'due_date'))->copy()->tz($this->response['timezone'])->format('d/m/Y');
                } else {
                    $temp[$i]['due_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $key['updated_at'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
                }
                $temp[$i]['start_date'] = $paddockPlanDate !== null ? $paddockPlanDate[0]['start_date'] : null;
                $temp[$i]['end_date'] = $paddockPlanDate !== null ? $paddockPlanDate[0]['end_date'] : null;
                $temp[$i]['paddock_plan_task_id'] = $temp[$i]['id'];
                $temp[$i]['task_status'] = $key['status'];
                $temp[$i]['batch_status'] = $batchStatus != null ? $batchStatus['status'] : null;
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $temp[$i]['spray_mix_id'], ['id', 'name']);
                $paddockArea = $temp[$i]['paddock']['spray_area'];
                $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($temp[$i]['id']);

                if ($temp[$i]['paddock'] != null) {
                    if ($con[1]['value'] == 'inprogress') {
                        $res[] = $temp[$i];
                    } else if ($con[1]['value'] == 'completed' && ((float)$paddockArea - $totalBatchArea) <= 0) {
                        $res[] = $temp[$i];
                    }
                }
                $i++;
            }
            $this->response['data'] = $res;
        }
        return $this->response();
    }

    public function retrievePaddockPlanTaskByParamsCompleted($column, $column2, $value)
    {
        $batch = DB::table('batches as T1')
            ->rightJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
            ->where('T1.' . $column, '=', $value)
            ->where('status', '=', 'completed')
            ->where('T1.deleted_at', '=', null)
            ->orderBy('T1.created_at', 'desc')->get()->toArray();
        $orders = OrderRequest::where($column, '=', $value)->orWhere($column2, '=', $value)->where('status', '=', 'completed')->orderBy('created_at', 'desc')->get();
        $orderArray = app($this->orderRequestClass)->manageResultsMobile($orders);
        $obj = array_merge($batch, $orderArray);
        $finalResult = [];
        if (sizeof($obj) > 0) {
            $i = 0;
            $array = json_decode(json_encode($obj), true);
            foreach ($array as $key) {
                if (!isset($array[$i]['code'])) {
                    $paddockId = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'paddock_id');
                    $array[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name']) : null;
                    if($array[$i]['paddock'] != null) {
                        $array[$i]['date_completed_orig'] = $key['updated_at'];
                        $array[$i]['date_completed'] = isset($key['updated_at']) ? Carbon::createFromFormat('Y-m-d H:i:s', $key['updated_at'])->copy()->tz($this->response['timezone'])->format('d M') : null;
                        $array[$i]['nickname'] = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'nickname');
                        $array[$i]['paddock_id'] = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'paddock_id');
                        $array[$i]['spray_mix_id'] = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'spray_mix_id');
                        $array[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $array[$i]['spray_mix_id'], ['id', 'name']);
                        $array[$i]['machine'] = app($this->machineClass)->getMachineNameByParams('id', $array[$i]['machine_id']);
                    }
                }else{
                    $array[$i]['date_completed'] = $key['delivered_date_formatted'];
                    $array[$i]['date_completed_orig'] = $key['updated_at'];
                }
                $i++;
            }
            $finalResult = collect($array);
            $finalResult = $finalResult->sortByDesc('date_completed_orig');
            $finalResult =  json_decode(json_encode($finalResult), true);
            $finalResult = array_values($finalResult);
            for ($a=0; $a <= sizeof($finalResult)-1; $a++) { 
                $items = $finalResult[$a];
                $finalResult[$a]['date_completed'] = isset($items['date_completed_orig']) ? Carbon::parse($items['date_completed_orig'])->format('d M') : null;
            }
        }
        return $finalResult;
    }

    public function retrievePaddockPlanTaskByParamsDue($column, $value)
    {
        $temp = Batch::leftJoin('batch_paddock_tasks as T1', 'T1.batch_id', '=', 'batches.id')
            ->where('batches.'.$column, '=', $value)
            ->where('batches.deleted_at', '=', null)
            ->where('status', '=', 'inprogress')
            ->take(5)->orderBy('batches.created_at', 'desc')->get();
        $finalResult = [];
        if (sizeof($temp) > 0) {
            $i = 0;
            foreach ($temp as $key) {
                $task = PaddockPlanTask::where('id', '=', $key['paddock_plan_task_id'])->first();
                $paddockId = $this->retrieveByParams('id', $key['paddock_plan_task_id'], 'paddock_id');
                $temp[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name']) : null;
                $temp[$i]['category'] = $this->retrieveByParams('id', $key['paddock_plan_task_id'], 'category');
                $temp[$i]['nickname'] = $this->retrieveByParams('id', $key['paddock_plan_task_id'], 'nickname');
                $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $key['paddock_plan_task_id'], 'paddock_plan_id');
                $temp[$i]['paddock_id'] = $this->retrieveByParams('id', $key['paddock_plan_task_id'], 'paddock_id');
                $temp[$i]['spray_mix_id'] = $this->retrieveByParams('id', $key['paddock_plan_task_id'], 'spray_mix_id');
                $temp[$i]['batch_status'] = $key['status'];
                $temp[$i]['due_date'] = $this->retrieveByParams('id', $key['paddock_plan_task_id'], 'due_date');
                $temp[$i]['due_date_format'] = isset($task['due_date']) ? Carbon::createFromFormat('Y-m-d', $task['due_date'])->copy()->tz($this->response['timezone'])->format('d M') : null;
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $temp[$i]['spray_mix_id'], ['id', 'name']);
                $i++;
            }
            $finalResult =  $temp;
        }
        return $finalResult;
    }

    public function retrievePaddockTaskByPaddock($paddockId)
    {
        $result = Paddock::where('id', '=', $paddockId)->get();
        if (sizeof($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }

    public function retrieveAvailablePaddock_Old(Request $request)
    {
        $data = $request->all();
        $date =  Carbon::now();
        $currDate = $date->toDateString();
        $tempRes  = Paddock::where('deleted_at', '=', null)->get();
        if (sizeof($tempRes) > 0) {
            $i = 0;
            $available = array();
            foreach ($tempRes as $key) {
                $task = PaddockPlanTask::where('paddock_id', '=', 7)
                    ->orderBy('due_date', 'asc')
                    ->first();
                if ($task !== null && $task['spray_mix_id'] == $data['spray_mix_id']) {
                    $paddockPlan = app($this->paddockPlanClass)->retrievePlanByParams('id', $task['paddock_plan_id'], ['start_date', 'end_date', 'crop_id', 'paddock_id']);
                    $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($task['id']);
                    $tempRes[$i]['area'] = (float)$key['area'];
                    $totalArea =  $totalBatchArea != null ? (doubleval($key['spray_area']) - doubleval($totalBatchArea)) : doubleval($key['spray_area']);
                    $tempRes[$i]['remaining_spray_area'] = $this->numberConvention($totalArea);
                    $tempRes[$i]['units'] = "Ha";
                    $tempRes[$i]['spray_areas'] = $tempRes[$i]['remaining_spray_area'];
                    $tempRes[$i]['batch_areas'] = $totalBatchArea;
                    $tempRes[$i]['spray_mix_units'] = "L/Ha";
                    $tempRes[$i]['partial'] = false;
                    $tempRes[$i]['paddock_id'] = $task['paddock_id'];
                    $tempRes[$i]['name'] = $key['name'];
                    $tempRes[$i]['spray_area'] = $key['spray_area'];
                    $tempRes[$i]['plan_task_id'] = $task['id'];
                    $tempRes[$i]['crop_name'] = app($this->cropClass)->retrieveCropName($paddockPlan[0]['crop_id']);
                    $tempRes[$i]['partial_flag'] = false;
                    $tempRes[$i]['due_date'] = $task['due_date'];
                    $tempRes[$i]['arable_area'] = $task['arable_area'];
                    $tempRes[$i]['rate_per_hectar'] = app('Increment\Marketplace\Paddock\Http\SprayMixProductController')->retrieveDetailsWithParams('spray_mix_id', $task['spray_mix_id'], ['rate']);
                    if ($tempRes[$i]['remaining_spray_area'] > 0) {
                        $available[] = $tempRes[$i];    
                    }
                }
                $i++;
            }
            $this->response['data'] = $available;
        } else {
            return $this->response['data'] = [];
        }
        return $this->response();
    }

    public function retrieveAvailablePaddocks(Request $request)
    {
        $data = $request->all();
        $result = [];
        $date =  Carbon::now();
        $currDate = $date->toDateString();
        $result = Paddock::where('deleted_at', '=', null)->where('merchant_id', '=', $data['merchant_id'])
            ->get();
        $finalResult = array();
        $counter = 0;
        if(sizeof($result) > 0){
            for ($i=0; $i <= sizeof($result)-1 ; $i++) {
                $item = $result[$i];
                $dates = [];
                $tasksPerPaddock = PaddockPlanTask::where('paddock_id', '=', $item['id'])->get();
                if(sizeof($tasksPerPaddock) > 0){
                    for ($b=0; $b <= sizeof($tasksPerPaddock)-1 ; $b++) {
                        $each = $tasksPerPaddock[$b];
                        $params = array(
                            array('paddock_id', '=', $each['paddock_id']),
                            array('due_date', '=', $each['due_date']),
                        );
                        $remainingSpray = $this->getRemainingSprayArea($params, $item);
                        if($remainingSpray !== null && $remainingSpray['remaining_spray_area'] > 0){
                            array_push($dates, $each);
                        }
                    }
                    usort($dates, function($a, $b) {return strtolower($a['due_date']) > strtolower($b['due_date']);});
                    $oldestDate = sizeof($dates) > 0 ? $dates[0] : null;
                    if($oldestDate !== null && $oldestDate['spray_mix_id'] == $data['spray_mix_id']) {
                        $paddockPlan = app($this->paddockPlanClass)->retrievePlanByParams('id', $each['paddock_plan_id'], ['start_date', 'end_date', 'crop_id', 'paddock_id']);
                        $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($each['id']);
                        $result[$i]['area'] = (float)$item['area'];
                        $totalArea =  $totalBatchArea != null ? (doubleval($item['spray_area']) - doubleval($totalBatchArea)) : doubleval($item['spray_area']);
                        $result[$i]['remaining_spray_area'] = $this->numberConvention($totalArea);
                        $result[$i]['units'] = "Ha";
                        $result[$i]['spray_areas'] = $result[$i]['remaining_spray_area'];
                        $result[$i]['batch_areas'] = $totalBatchArea;
                        $result[$i]['spray_mix_units'] = "L/Ha";
                        $result[$i]['partial'] = false;
                        $result[$i]['spray_area'] = $item['spray_area'];
                        $result[$i]['paddock_id'] = $each['paddock_id'];
                        $result[$i]['name'] = $item['name'];
                        $result[$i]['plan_task_id'] = $each['id'];
                        $result[$i]['crop_name'] = app($this->cropClass)->retrieveCropName($paddockPlan[0]['crop_id']);
                        $result[$i]['partial_flag'] = false;
                        $result[$i]['due_date'] = $each['due_date'];
                        $result[$i]['arable_area'] = $item['arable_area'];
                        $result[$i]['rate_per_hectar'] = app('Increment\Marketplace\Paddock\Http\SprayMixProductController')->retrieveDetailsWithParams('spray_mix_id', $each['spray_mix_id'], ['rate']);
                        if($result[$i]['remaining_spray_area'] > 0){
                            array_push($finalResult, $result[$i]);
                        }
                    }
                }
            }
        }
        $this->response['data'] = $finalResult;
        return $this->response(); 
    }

    public function retrieveByParams($column, $value, $returns)
    {
        $result = PaddockPlanTask::where($column, '=', $value)->where('deleted_at', '=', null)->select($returns)->get();
        return sizeof($result) > 0 ? $result[0][$returns] : null;
    }

    public function checkIfAvailable(Request $request)
    {
        $data = $request->all();
        $i = 0;
        foreach ($data['selectedPaddocks'] as $key) {
            $paddocks = app($this->paddockClass)->getByParams('id', $key['paddock_id'], ['id', 'name', 'spray_area']);
            $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($key['plan_task_id']);
            if (((int)$totalBatchArea + (int)$key['remaining_spray_area']) > (int)$paddocks['spray_area']) {
                $this->response['error'] = 'Unavailable paddocks';
                $this->response['data'] = [];
            } else {
                $this->response['error'] = null;
                $this->response['data'] = 'Available';
            }
            $i++;
        }
        return $this->response();
    }

    public function retrieveFirst($condition){
        return $result = PaddockPlanTask::where($condition)->orderBy('due_date', 'asc')->first();
    }
}
