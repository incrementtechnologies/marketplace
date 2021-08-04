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
        $result = DB::table('batches as T1')
            ->rightJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
            ->rightJoin('paddock_plans_tasks as T3', 'T2.paddock_plan_task_id', '=', 'T3.id')
            ->rightJoin('paddocks as T4', 'T3.paddock_id', '=', 'T4.id')
            ->where(function ($query) use ($con) {
                $query->where('T1.' . $con[1]['column'], '=', 'partially_completed')
                    ->orWhere('T3.' . $con[1]['column'], '=', $con[1]['value'])
                    ->orWhere('T3.' . $con[1]['column'], '=', 'partially_completed');
            })
            ->where('T1.deleted_at', '=', null)
            // ->where('T4.' . $con[0]['column'], '=', $con[0]['value'])
            ->select(
                'T1.updated_at as dateCompleted',
                'T1.spray_mix_id as batch_spray_mix',
                'T1.*',
                'T2.*',
                'T4.spray_area',
                'T4.arable_area',
                'T4.short_description',
                'T4.name',
                'T4.area',
                'T4.note',
                'T4.id as paddock_id',
                'T3.due_date',
                'T3.status',
                'T3.id as task_id'
            )
            ->skip($data['offset'])->take($data['limit'])->orderBy('T1.created_at', 'desc')->get();
        // $result = Paddock::where($con[0]['column'], '=', $con[0]['value'])->skip($data['offset'])->take($data['limit'])->get();
        $currDate = Carbon::now()->toDateString();
        $finalResult = array();
        if (sizeof($result) > 0) {
            $result = json_decode(json_encode($result), true);
            // dd($result);
            $i = 0;
            foreach ($result as $key) {
                // dd($result);
                $task = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])
                    ->where('paddock_id', '=', $key['paddock_id'])
                    ->where(function ($query) use ($con) {
                        $query->where($con[1]['column'], '=', $con[1]['value'])
                            ->orWhere($con[1]['column'], '=', 'partially_completed');
                    })
                    ->orderBy('due_date', 'asc')
                    ->limit(1)
                    ->get();
                if (sizeof($task) > 0) {
                    $paddockPlan = app($this->paddockPlanClass)->retrievePlanByParams('id', $task[0]['paddock_plan_id'], ['start_date', 'end_date', 'crop_id', 'paddock_id']);
                    if (sizeof($paddockPlan) > 0 && ($paddockPlan[0]['start_date'] <= $currDate && $currDate <= $paddockPlan[0]['end_date'])) {
                        // dd($paddockPlan);
                        $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($task[0]['id']);
                        $result[$i]['area'] = (float)$key['area'];
                        $totalArea =  $totalBatchArea != null ? ((float)$key['spray_area'] - (float)$totalBatchArea) : (float)$key['spray_area'];
                        $result[$i]['remaining_spray_area'] = $this->numberConvention($totalArea);
                        $result[$i]['due_date'] = Carbon::createFromFormat('Y-m-d', $key['due_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
                        $result[$i]['category'] = $this->retrieveByParams('id', $task[0]['id'], 'category');
                        $result[$i]['id'] =  $task[0]['id'];
                        $result[$i]['paddock_plan_task_id'] =  $key['task_id'];
                        $result[$i]['nickname'] = $this->retrieveByParams('id', $task[0]['id'], 'nickname');
                        $result[$i]['machine'] = app($this->batchPaddockTaskClass)->getMachinedByBatches('paddock_plan_task_id', $task[0]['id']);
                        $result[$i]['spray_mix_id'] = $this->retrieveByParams('id', $task[0]['id'], 'spray_mix_id');
                        $result[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $task[0]['spray_mix_id'], ['id', 'name']);
                        $result[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $task[0]['id'], 'paddock_plan_id');
                        $result[$i]['paddock_id'] = $this->retrieveByParams('id', $task[0]['id'], 'paddock_id');
                        $result[$i]['paddock'] = array(
                            'name' => $key['name'],
                            'spray_area' => $key['spray_area'],
                            'id' => $key['id']
                        );
                        if (isset($temp[$i]['paddock']['crop_name'])) {
                            $temp[$i]['paddock']['crop_name'] = app($this->cropClass)->retrieveCropById($paddockPlan[0]['crop_id'])[0]->name;
                        }
                        if ($result[$i]['remaining_spray_area'] > 0) {
                            array_push($finalResult, $result[$i]);
                        }
                    }
                }
                $i++;
            }
            $this->response['data'] = $finalResult;
            return $this->response();
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

    public function retrieveMobileByParamsEndUser(Request $request)
    {
        $data = $request->all();
        $con = $data['condition'];
        if ($con[1]['value'] == 'inprogress') {
            $result = DB::table('batches as T1')
                ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
                ->where('T1.' . $con[0]['column'], '=', $con[0]['value'])
                ->where('T1.deleted_at', '=', null)
                ->where('T1.status', '=', 'inprogress')
                ->select('T1.updated_at as dateCompleted', 'T1.spray_mix_id as batch_spray_mix', 'T1.*', 'T2.*')
                ->skip($data['offset'])->take($data['limit'])->orderBy('T1.created_at', 'desc')->get();
        } else {
            $result = DB::table('batches as T1')
                ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
                ->where('T1.' . $con[0]['column'], '=', $con[0]['value'])
                ->where('T1.' . $con[1]['column'], '=', $con[1]['value'])
                ->where('T1.deleted_at', '=', null)
                ->select('T1.updated_at as dateCompleted', 'T1.spray_mix_id as batch_spray_mix', 'T1.*', 'T2.*')
                ->skip($data['offset'])->take($data['limit'])->orderBy('T1.created_at', 'desc')->get();
        }
        $obj = $result;
        if (sizeof($obj) > 0) {
            $i = 0;
            $temp = json_decode(json_encode($obj), true);
            $res = [];
            foreach ($temp as $key) {
                $paddockId = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_id');
                $temp[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name', 'spray_area']) : null;
                $paddoctId = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_plan_id');
                $paddockPlanDate = app($this->paddockPlanClass)->retrievePlanByParams('id', $paddoctId, ['start_date', 'end_date']);
                if ($con[1]['value'] == 'inprogress') {
                    $paddockPlanDate[0]['start_date'] = Carbon::createFromFormat('Y-m-d', $paddockPlanDate[0]['start_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
                    $temp[$i]['due_date'] = $paddockPlanDate !== null ? $paddockPlanDate[0]['start_date'] : Carbon::createFromFormat('Y-m-d',  $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'due_date'))->copy()->tz($this->response['timezone'])->format('d/m/Y');
                } else {
                    $temp[$i]['due_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $key['dateCompleted'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
                }
                $temp[$i]['category'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'category');
                $temp[$i]['start_date'] = $paddockPlanDate !== null ? $paddockPlanDate[0]['start_date'] : null;
                $temp[$i]['end_date'] = $paddockPlanDate !== null ? $paddockPlanDate[0]['end_date'] : null;
                $temp[$i]['nickname'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'nickname');
                $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_plan_id');
                $temp[$i]['paddock_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_id');
                $temp[$i]['spray_mix_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'spray_mix_id');
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $temp[$i]['batch_spray_mix'], ['id', 'name']);
                $temp[$i]['machine'] = app($this->machineClass)->getMachineNameByParams('id', $temp[$i]['machine_id']);
                $paddockArea = $temp[$i]['paddock']['spray_area'];
                $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($temp[$i]['paddock_plan_task_id']);

                if ($temp[$i]['paddock'] != null) {
                    if ($con[1]['value'] == 'approved') {
                        $res[] = $temp[$i];
                    } else if ($con[1]['value'] == 'completed' && ((double)$paddockArea - $totalBatchArea) <= 0) {
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
            ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
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
                    // dd($array);
                    $paddockId = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'paddock_id');
                    $array[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name']) : null;
                    if ($array[$i]['paddock'] != null) {
                        $array[$i]['date_completed'] = isset($key['updated_at']) ? Carbon::createFromFormat('Y-m-d H:i:s', $key['updated_at'])->copy()->tz($this->response['timezone'])->format('d M') : null;
                        $array[$i]['nickname'] = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'nickname');
                        $array[$i]['paddock_id'] = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'paddock_id');
                        $array[$i]['spray_mix_id'] = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'spray_mix_id');
                        $array[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $array[$i]['spray_mix_id'], ['id', 'name']);
                        $array[$i]['machine'] = app($this->machineClass)->getMachineNameByParams('id', $array[$i]['machine_id']);
                    }
                }
                $i++;
            }
            $finalResult = $array;
        }
        return $finalResult;
    }

    public function retrievePaddockPlanTaskByParamsDue($column, $value)
    {
        $result = DB::table('batches as T1')
            ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
            ->where('T1.' . $column, '=', $value)
            ->where('T1.deleted_at', '=', null)
            ->where('T1.status', '=', 'inprogress')
            ->take(5)->orderBy('T1.created_at', 'desc')->get();
        $obj = $result;
        $finalResult = [];
        if (sizeof($obj) > 0) {
            $i = 0;
            $temp = json_decode(json_encode($obj), true);
            foreach ($temp as $key) {
                $paddockId = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_id');
                $temp[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name']) : null;
                $temp[$i]['category'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'category');
                $temp[$i]['nickname'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'nickname');
                $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_plan_id');
                $temp[$i]['paddock_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_id');
                $temp[$i]['spray_mix_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'spray_mix_id');
                $temp[$i]['due_date'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'due_date');
                $temp[$i]['due_date_format'] = isset($temp[$i]['due_date']) ? Carbon::createFromFormat('Y-m-d', $temp[$i]['due_date'])->copy()->tz($this->response['timezone'])->format('d M') : null;
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $temp[$i]['spray_mix_id'], ['id', 'name']);
                $temp[$i]['machine'] = app($this->machineClass)->getMachineNameByParams('id', $temp[$i]['machine_id']);
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

    public function retrieveAvailablePaddocks(Request $request)
    {
        $data = $request->all();
        $returnResult = array();
        $date =  Carbon::now();
        $currDate = $date->toDateString();
        // dd($currDate);
        $result = DB::table('paddock_plans_tasks as T1')
            ->leftJoin('paddocks as T2', 'T1.paddock_id', '=', 'T2.id')
            ->leftJoin('paddock_plans as T3', 'T3.id', '=', 'T1.paddock_plan_id')
            ->leftJoin('crops as T4', 'T4.id', '=', 'T3.crop_id')
            ->leftJoin('spray_mixes as T5', 'T5.id', '=', 'T1.spray_mix_id')
            ->where('T1.spray_mix_id', '=', $data['spray_mix_id'])
            ->where('T1.status', '!=', 'pending')
            ->where('T1.deleted_at', '=', null)
            ->where('T2.merchant_id', $data['merchant_id'])
            ->groupBy('T1.paddock_plan_id')
            ->get(['T1.*', 'T2.*', 'T3.start_date', 'T3.end_date', 'T4.name as crop_name', 'T5.name as mix_name', 'T5.application_rate', 'T5.minimum_rate', 'T5.maximum_rate', 'T1.id as plan_task_id', 'T1.deleted_at']);
        if (sizeof($result) > 0) {
            $tempRes = json_decode(json_encode($result), true);
            $i = 0;
            $available = array();
            foreach ($tempRes as $key) {
                // dd($key['paddock_id']);
                if ($tempRes[$i]['start_date'] <= $currDate && $currDate <= $tempRes[$i]['end_date']) {
                    $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($tempRes[$i]['plan_task_id']);
                    $tempRes[$i]['area'] = (float)$tempRes[$i]['area'];
                    $totalArea =  $totalBatchArea != null ? (doubleval($tempRes[$i]['spray_area']) - doubleval($totalBatchArea)) : doubleval($tempRes[$i]['spray_area']);
                    $tempRes[$i]['remaining_spray_area'] = $this->numberConvention($totalArea);
                    $tempRes[$i]['units'] = "Ha";
                    $tempRes[$i]['spray_areas'] = $tempRes[$i]['remaining_spray_area'];
                    $tempRes[$i]['batch_areas'] = $totalBatchArea;
                    $tempRes[$i]['spray_mix_units'] = "L/Ha";
                    $tempRes[$i]['partial'] = false;
                    $tempRes[$i]['partial_flag'] = false;
                    $tempRes[$i]['rate_per_hectar'] = app('Increment\Marketplace\Paddock\Http\SprayMixProductController')->retrieveDetailsWithParams('spray_mix_id', $tempRes[$i]['spray_mix_id'], ['rate']);
                    if ($tempRes[$i]['remaining_spray_area'] > 0) {
                        $available[] = $tempRes[$i];
                    }
                }
                $i++;
            }
            // dd($tempRes);
            $this->response['data'] = $available;
        } else {
            return $this->response['data'] = [];
        }
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
        foreach ($data['selectedPaddocks'] as $key => $value) {
            $paddocks = app($this->paddockClass)->getByParams('id', $value['paddock_id'], ['id', 'name', 'spray_area']);
            $totalBatchArea = app($this->batchPaddockTaskClass)->getTotalBatchPaddockPlanTask($value['plan_task_id']);
            if (((int)$totalBatchArea + (int)$key['remaining_spray_area']) > $paddocks['spray_area']) {
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
}
