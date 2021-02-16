<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaddockPlanTaskController extends APIController
{
  
  public $paddockClass = 'Increment\Marketplace\Paddock\Http\PaddockController';
  public $sprayMixClass = 'Increment\Marketplace\Paddock\Http\SprayMixController';
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
                $temp[$i]['paddock'] = app($this->paddockClass)->getByParams('id', $key['paddock_id'], ['id', 'name']);
                $temp[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $key['paddock_id'], ['id', 'name']);
                $temp[$i]['machine'] = app($this->batchPaddockTaskClass)->getMachinedByBatches('paddock_plan_task_id', $key['id']);
                $i++;
            }
            $this->response['data'] = $temp;
        }
        return $this->response();
    }

    public function retrievePaddockPlanTaskByParamsCompleted($column, $value){
        $result = PaddockPlanTask::where($column, '=', $value)->where('status', '=', 'approved')->orWhere('status', '=', 'completed')->get();
        if(sizeof($result) > 0){
            $i = 0;
            foreach ($result as $key) {
                $result[$i]['due_date'] = Carbon::createFromFormat('Y-m-d', $key['due_date'])->copy()->tz($this->response['timezone'])->format('d M');
                $result[$i]['paddock'] = app($this->paddockClass)->getByParams('id', $result[$i]['paddock_id'], ['id', 'name']);
                $result[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $result[$i]['paddock_id'], ['id', 'name']);
                $i++;
            }
        }
        return $result;
    }

    public function retrievePaddockPlanTaskByParamsDue($column, $value){
        $result = PaddockPlanTask::where($column, '=', $value)->where('status', '=', 'pending')->orWhere('status', '=', 'in_progress')->orderBy('due_date', 'desc')->get();
        if(sizeof($result) > 0){
            $i = 0;
            foreach ($result as $key) {
                $result[$i]['due_date'] = Carbon::createFromFormat('Y-m-d', $key['due_date'])->copy()->tz($this->response['timezone'])->format('d M');
                $result[$i]['paddock'] = app($this->paddockClass)->getByParams('id', $result[$i]['paddock_id'], ['id', 'name']);
                $result[$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $result[$i]['paddock_id'], ['id', 'name']);
                $i++;
            }
        }
        return $result;
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
}
