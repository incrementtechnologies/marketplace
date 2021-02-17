<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\SprayMix;
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
        if($con[1]['value'] == 'inprogress'){
            $result = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])
                ->where(function($query){
                    $query->where('status', '=', 'pending')
                            ->orWhere('status', '=', 'inprogress');
                })->get();
        }else{
            $result = PaddockPlanTask::where($con[0]['column'], '=', $con[0]['value'])->where($con[1]['column'], '=', $con[1]['value'])->get();
        }
        $temp = $result;
        if(sizeof($temp) > 0){
            $i = 0;
            foreach ($temp as $key) {
                $paddocks = app($this->paddockPlanClass)->retrievePlanByParams('id', $key['paddock_plan_id'], 'crop_id');
                $temp[$i]['paddock'] = app($this->paddockClass)->getByParams('id', $key['paddock_id'], ['id', 'name']);
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $key['paddock_id'], ['id', 'name']);
                $temp[$i]['machine'] = app($this->batchPaddockTaskClass)->getMachinedByBatches('paddock_plan_task_id', $key['id']);
                $temp[$i]['crop_name'] = app($this->cropClass)->retrieveCropById($paddocks[0]['crop_id'])[0]['name'];
                $i++;
            }
            $this->response['data'] = $temp;
        }
        return $this->response();
    }

    public function retrieveMobileByParamsEndUser(Request $request){
        $data = $request->all();
        $con = $data['condition'];
        if($con[1]['value'] == 'inprogress'){
            $result = Batch::where($con[0]['column'], '=', $con[0]['value'])
                ->where(function($query){
                    $query->where('status', '=', 'pending')
                            ->orWhere('status', '=', 'inprogress');
                })->get();
        }else{
            $result = Batch::where($con[0]['column'], '=', $con[0]['value'])->where($con[1]['column'], '=', $con[1]['value'])->get();
        }
        // dd($result);
        $temp = $result;
        if(sizeof($temp) > 0){
            $i = 0;
            foreach ($temp as $key) {
                $temp[$i]['paddock'] = app($this->paddockClass)->getByParams('merchant_id', $con[0]['value'], ['id', 'name']);
                $temp[$i]['due_date'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']->id, 'due_date');
                $temp[$i]['category'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']->id, 'category');
                $temp[$i]['nickname'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']->id, 'nickname');
                $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']->id, 'paddock_plan_id');
                $temp[$i]['paddock_id'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']->id, 'paddock_id');
                $temp[$i]['spray_mix_id'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']->id, 'spray_mix_id');
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('merchant_id', $con[0]['value'], ['id', 'name']);
                $temp[$i]['machine'] = app($this->machineClass)->getMachineNameByParams('id', $key['machine_id']);
                $i++;
            }
            $this->response['data'] = $temp;
        }
        return $this->response();
    }

    public function retrievePaddockPlanTaskByParamsCompleted($column, $value){
        $result = PaddockPlanTask::where($column, '=', $value)->where('status', '=', 'approved')->orWhere('status', '=', 'completed')->get()->toArray();
        $batch = Batch::where($column, '=', $value)->where('status', '=', 'completed')->get()->toArray();
        $array = array_merge($result, $batch);
        if(sizeof($result) > 0 || sizeof($batch)){
            $i = 0;
            foreach ($array as $key) {
                $array[$i]['paddock'] = isset($array[$i]['paddock_id']) ? app($this->paddockClass)->getByParams('id', $array[$i]['paddock_id'], ['id', 'name']) : app($this->paddockClass)->getByParams('merchant_id', $value, ['id', 'name']);
                $array[$i]['due_date'] = isset($array[$i]['paddock_id']) ? Carbon::createFromFormat('Y-m-d', $key['due_date'])->copy()->tz($this->response['timezone'])->format('d M') : $this->retrieveByParams('paddock_id', $array[$i]['paddock']['id'], 'due_date');;
                $array[$i]['spray_mix'] = isset($array[$i]['paddock_id']) ? app($this->sprayMixClass)->getByParams('id', $array[$i]['paddock_id'], ['id', 'name']) : app($this->sprayMixClass)->getByParams('merchant_id', $value, ['id', 'name']);
                $i++;
            }
        }
        return $array;
    }

    public function retrievePaddockPlanTaskByParamsDue($column, $value){
        $result = Batch::where($column, '=', $value)
                    ->where(function($query){
                        $query->where('status', '=', 'pending')
                                ->orWhere('status', '=', 'inprogress')
                                ->orWhere('status', '=', 'ongoing');
                    })->orderBy('created_at', 'desc')->limit(5)->get();
        $temp = $result;
        if(sizeof($temp) > 0){
            $i = 0;
            foreach ($temp as $key) {
                $temp[$i]['paddock'] = app($this->paddockClass)->getByParams('merchant_id', $value, ['id', 'name']);
                $temp[$i]['category'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']['id'], 'category');
                $temp[$i]['nickname'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']['id'], 'nickname');
                $temp[$i]['paddock_plan_id'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']['id'], 'paddock_plan_id');
                $temp[$i]['paddock_id'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']->id, 'paddock_id');
                $temp[$i]['spray_mix_id'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']['id'], 'spray_mix_id');
                $temp[$i]['due_date'] = $this->retrieveByParams('paddock_id', $temp[$i]['paddock']['id'], 'due_date');
                $temp[$i]['due_date_format'] = Carbon::createFromFormat('Y-m-d', $temp[$i]['due_date'])->copy()->tz($this->response['timezone'])->format('d M');
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('merchant_id', $value, ['id', 'name']);
                $temp[$i]['machine'] = app($this->machineClass)->getMachineNameByParams('id', $key['machine_id']);
                $i++;
            }
        }
        return $temp;
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
        $result = DB::table('paddock_plans_tasks as T1')
                ->leftJoin('paddocks as T2', 'T1.paddock_id', '=', 'T2.id')
                ->leftJoin('paddock_plans as T3', 'T3.id', '=', 'T1.paddock_plan_id')
                ->leftJoin('crops as T4', 'T4.id', '=', 'T3.crop_id')
                ->leftJoin('spray_mixes as T5', 'T5.id', '=', 'T1.spray_mix_id')
                ->where('T1.spray_mix_id', '=', $data['spray_mix_id'])
                ->where('T2.merchant_id', $data['merchant_id'])
                ->get(['T1.*', 'T2.*', 'T3.*', 'T4.name as crop_name', 'T5.name as mix_name', 'T5.application_rate', 'T5.minimum_rate', 'T5.maximum_rate']);
        if(sizeof($result) > 0){
            $tempRes = json_decode(json_encode($result), true);
            $i = 0;
            foreach ($tempRes as $key) {
                $tempRes[$i]['area'] = (int)$tempRes[$i]['area'];
                $tempRes[$i]['remaining_area'] = (int)$tempRes[$i]['area'];
                $tempRes[$i]['units'] = "Ha";
                $tempRes[$i]['spray_mix_units'] = "L/Ha";
                $tempRes[$i]['partial'] = false;
                $tempRes[$i]['partial_flag'] = false;

                $i++;
            }
            $this->response['data'] = $tempRes;
        }else{
            return $this->response['data'] = [];
        }
        return $this->response();

    }

    public function retrieveByParams($column, $value, $returns){
        $result = PaddockPlanTask::where($column, '=', $value)->where('deleted_at', '=', null)->select($returns)->get();
        return sizeof($result) > 0 ? $result[0][$returns] : null;  
    }
}
