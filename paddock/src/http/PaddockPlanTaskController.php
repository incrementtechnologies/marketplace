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


  function __construct(){
    $this->model = new PaddockPlanTask();
    $this->notRequired = array();
  }
  
  public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new PaddockPlanTask();
      $this->retrieveDB($data);
      for ($i=0; $i < count($this->response['data']); $i++){
           $spraymixdata= SprayMix::select('name')->where('id','=', $this->response['data'][$i]['spray_mix_id'])->get();
           if (count($spraymixdata) != 0){
              $this->response['data'][$i]['spray_mix_name'] = $spraymixdata[0]['name'];
           }
      }
      return $this->response();
  }

  public function retrieveTaskByPaddock($paddockPlanId){
      $result = PaddockPlanTask::where('paddock_plan_id', '=', $paddockPlanId)->get(['spray_mix_id', 'id', 'paddock_plan_id', 'due_date']);
      if(sizeof($result) > 0){
          return $result;
      }else{
          return null;
      }
  }

    public function retrieveMobileByParams(Request $request){
        $data = $request->all();
        $con = $data['condition'];
        $result = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])->where($con[1]['column'], '=', $con[1]['value'])->skip($data['offset'])->orderBy('created_at', 'desc')->take($data['limit'])->get();
        $temp = $result;
        $finalResult = array();
        $date =  Carbon::now();
        $currDate = $date->toDateString();
        if(sizeof($temp) > 0){
            $i = 0;
            $j = 1;
            foreach ($temp as $key) {
                if($currDate <= $temp[$i]['due_date']){
                    $paddocks = app($this->paddockPlanClass)->retrievePlanByParams('id', $key['paddock_plan_id'], ['crop_id', 'paddock_id']);
                    $existInBatch = app($this->batchPaddockTaskClass)->retrieveByParams('paddock_plan_task_id', $temp[$i]['id'], ['id']);
                    if(sizeof($existInBatch) <= 0) {
                        $temp[$i]['paddock'] = app($this->paddockClass)->getByParams('id', $paddocks[0]['paddock_id'], ['id', 'name']);
                        if($temp[$i]['paddock'] !== null){
                            $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $key['paddock_id'], ['id', 'name']);
                            $temp[$i]['due_date'] = $this->retrieveByParams('id', $temp[$i]['id'], 'due_date');
                            $temp[$i]['category'] = $this->retrieveByParams('id', $temp[$i]['id'], 'category');
                            $temp[$i]['nickname'] = $this->retrieveByParams('id', $temp[$i]['id'], 'nickname');
                            $temp[$i]['machine'] = app($this->batchPaddockTaskClass)->getMachinedByBatches('paddock_plan_task_id', $temp[$i]['id']);
                            $temp[$i]['spray_mix_id'] = $this->retrieveByParams('id', $temp[$i]['id'], 'spray_mix_id');
                            $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $temp[$i]['spray_mix_id'], ['id', 'name']);
                            $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $temp[$i]['id'], 'paddock_plan_id');
                            $temp[$i]['paddock_id'] = $this->retrieveByParams('id', $temp[$i]['id'], 'paddock_id');
                            if(isset($temp[$i]['paddock']['crop_name'])){
                                $temp[$i]['paddock']['crop_name'] = app($this->cropClass)->retrieveCropById($paddocks[0]['crop_id'])[0]->name;
                            }
                            $finalResult[] = $temp[$i];
                        }
                    }
                }
                $i++;
            }
            $this->response['data'] = $finalResult;
        }
        return $this->response();
    }

    public function retrieveMobileByParamsEndUser(Request $request){
        $data = $request->all();
        $con = $data['condition'];
        if($con[1]['value'] == 'inprogress'){
            $result = DB::table('batches as T1')
                    ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
                    ->where('T1.'.$con[0]['column'], '=', $con[0]['value'])
                    ->where('T1.deleted_at', '=', null)
                    ->where(function($query){
                        $query->where('T1.status', '=', 'pending')
                                ->orWhere('T1.status', '=', 'inprogress');
                    })->skip($data['offset'])->take($data['limit'])->orderBy('T1.created_at', 'desc')->get();
               
        }else{
            $result = DB::table('batches as T1')
                    ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
                    ->where('T1.'.$con[0]['column'], '=', $con[0]['value'])
                    ->where('T1.'.$con[1]['column'], '=', $con[1]['value'])
                    ->where('T1.deleted_at', '=', null)
                    ->skip($data['offset'])->take($data['limit'])->orderBy('T1.created_at', 'desc')->get();
        }
        $obj = $result;
        if(sizeof($obj) > 0){
            $i = 0;
            $temp = json_decode(json_encode($obj), true);
            foreach ($temp as $key) {
                // dd($temp);
                $paddockId = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_id');
                $temp[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name']) : null;
                if($temp[$i]['paddock'] == null){
                    $temp = null;
                }else{
                    $paddoctId = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_plan_id');
                    $paddockPlanDate = app($this->paddockPlanClass)->retrievePlanByParams('id', $paddoctId, ['start_date']);
                    $paddockPlanDate[0]['start_date'] = Carbon::createFromFormat('Y-m-d', $paddockPlanDate[0]['start_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
                    $temp[$i]['due_date'] = $paddockPlanDate !== null ? $paddockPlanDate[0]['start_date'] : Carbon::createFromFormat('Y-m-d',  $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'due_date'))->copy()->tz($this->response['timezone'])->format('d/m/Y');
                    $temp[$i]['category'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'category');
                    $temp[$i]['nickname'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'nickname');
                    $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_plan_id');
                    $temp[$i]['paddock_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'paddock_id');
                    $temp[$i]['spray_mix_id'] = $this->retrieveByParams('id', $temp[$i]['paddock_plan_task_id'], 'spray_mix_id');
                    $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $temp[$i]['spray_mix_id'], ['id', 'name']);
                    $temp[$i]['machine'] = app($this->machineClass)->getMachineNameByParams('id', $temp[$i]['machine_id']);
                }
                $i++;
            }
            $this->response['data'] = $temp;
        }
        return $this->response();
    }

    public function retrievePaddockPlanTaskByParamsCompleted($column, $column2, $value){
        $batch = DB::table('batches as T1')
                ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
                ->where('T1.'.$column, '=', $value)
                ->where('status', '=', 'completed')
                ->where('T1.deleted_at', '=', null)
                ->orderBy('T1.created_at', 'desc')->get()->toArray();
        $orders = OrderRequest::where($column, '=', $value)->orWhere($column2, '=', $value)->where('status', '=', 'completed')->orderBy('created_at', 'desc')->get();
        $orderArray = app($this->orderRequestClass)->manageResultsMobile($orders);
        $obj = array_merge($batch, $orderArray);
        $finalResult = [];
        if(sizeof($obj) > 0){
            $i = 0;
            $array = json_decode(json_encode($obj), true);
            foreach ($array as $key) {
                if(!isset($array[$i]['code'])){
                    // dd($array);
                    $paddockId = $this->retrieveByParams('id', $array[$i]['paddock_plan_task_id'], 'paddock_id');
                    $array[$i]['paddock'] = $paddockId != null ? app($this->paddockClass)->getByParams('id', $paddockId, ['id', 'name']) : null;
                    if($array[$i]['paddock'] != null){
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

    public function retrievePaddockPlanTaskByParamsDue($column, $value){
        $result = DB::table('batches as T1')
                ->leftJoin('batch_paddock_tasks as T2', 'T1.id', '=', 'T2.batch_id')
                ->where('T1.'.$column, '=', $value)
                ->where('T1.deleted_at', '=', null)
                ->where(function($query){
                    $query->where('T1.status', '=', 'inprogress')
                            ->orWhere('T1.status', '=', 'ongoing');
                })->take(5)->orderBy('T1.created_at', 'desc')->get();
        $obj = $result;
        $finalResult = [];
        if(sizeof($obj) > 0){
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

    public function retrievePaddockTaskByPaddock($paddockId){
        $result = Paddock::where('id', '=', $paddockId)->get();
        if(sizeof($result) > 0){
            return $result;
        }else{
            return null;
        }
    }

    public function retrieveAvailablePaddocks(Request $request){
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
                ->where('T1.status', '=', 'approved')
                ->where('T2.deleted_at', '=', null)
                ->where('T1.deleted_at', '=', null)
                ->whereNull('T2.deleted_at')
                ->where('T2.merchant_id', $data['merchant_id'])
                ->groupBy('T2.id')
                ->get(['T1.*', 'T2.*', 'T3.start_date', 'T3.end_date', 'T4.name as crop_name', 'T5.name as mix_name', 'T5.application_rate', 'T5.minimum_rate', 'T5.maximum_rate', 'T1.id as plan_task_id', 'T1.deleted_at']);
        if(sizeof($result) > 0){
            $tempRes = json_decode(json_encode($result), true);
            $i = 0;
            $available = array();
            foreach ($tempRes as $key) {
                if($tempRes[$i]['start_date'] <= $currDate && $currDate <= $tempRes[$i]['end_date']){
                    $totalBatchArea = $this->getTotalBatchPaddockPlanTask($tempRes[$i]['plan_task_id']);
                    $tempRes[$i]['area'] = (float)$tempRes[$i]['area'];
                    $totalArea =  $totalBatchArea != null ? ((float)$tempRes[$i]['spray_area'] - (float)$totalBatchArea) : (float)$tempRes[$i]['spray_area'];
                    $tempRes[$i]['remaining_spray_area'] = $this->numberConvention($totalArea);
                    $tempRes[$i]['units'] = "Ha";
                    $tempRes[$i]['spray_mix_units'] = "L/Ha";
                    $tempRes[$i]['partial'] = false;
                    $tempRes[$i]['partial_flag'] = false;
                    $tempRes[$i]['rate_per_hectar'] = app('Increment\Marketplace\Paddock\Http\SprayMixProductController')->retrieveDetailsWithParams('spray_mix_id', $tempRes[$i]['spray_mix_id'], ['rate']);
                    if($tempRes[$i]['remaining_spray_area'] > 0){
                        $available[] = $tempRes[$i];
                    }
                }
                $i++;
            }
            $this->response['data'] = $available;
        }else{
            return $this->response['data'] = [];
        }
        return $this->response();

    }

    public function retrieveByParams($column, $value, $returns){
        $result = PaddockPlanTask::where($column, '=', $value)->where('deleted_at', '=', null)->select($returns)->get();
        return sizeof($result) > 0 ? $result[0][$returns] : null;  
    }

    public function getTotalBatchPaddockPlanTask($paddockPlanTaskId){
        $result = DB::table('batch_paddock_tasks as T1')
                ->leftJoin('batch_products as T2', 'T2.batch_id', '=', 'T1.batch_id')
                ->where('T1.paddock_plan_task_id', '=', $paddockPlanTaskId)
                ->groupBy('T1.paddock_plan_task_id')
                ->select(DB::raw('SUM(T2.applied_rate) as total_area'))
                ->get();
        return sizeof($result) > 0 ? $result[0]->total_area : null;
    }
}
