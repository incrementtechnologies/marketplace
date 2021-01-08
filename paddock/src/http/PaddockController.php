<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\Batch;
use Increment\Marketplace\Paddock\Models\PaddockPlan;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Increment\Marketplace\Paddock\Models\Crop;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaddockController extends APIController
{
  public $batchPaddockTaskClass = 'Increment\Marketplace\Paddock\Http\BatchPaddockTaskController';
  public $sprayMixClass = 'Increment\Marketplace\Paddock\Http\SprayMixController';
  //
  function __construct(){
      $this->model = new Paddock();
      $this->notRequired = array(
          'note'
      );
  }    

  public function retrieve(Request $request){
    $data = $request->all();
    if (isset($data['id'])) {
        $result = Paddock::where("id", "=", $data['id'])
                ->where("merchant_id", "=", $data['merchant_id'])
                ->where("status", "=", $data['status'])
                ->get();
        $result['paddock_data'] = PaddockPlan::select()->where("paddock_id", "=", $data['id'])->orderBy('start_date','desc')->limit(2)->get();
        $paddock_plan_tasks = PaddockPlanTask::select()->where("paddock_plan_id", "=", $result['paddock_data'][0]['id'])->get();
        $result['paddock_data'][0]['paddock_tasks_data'] = $paddock_plan_tasks;
        for($i=0; $i<count($result['paddock_data']); $i++){
            $result['paddock_data'][$i]['crop_name'] =  Crop::select("name")->where("id", "=", $result['paddock_data'][$i]['crop_id'])->get();
        }
        $this->response['data'] = $result;
    }else{
        $data = $request->all();
        $this->model = new Paddock();
        $this->retrieveDB($data);
        for ($i = 0; $i < count($this->response['data']); $i++){
            $paddockData = $this->response['data'];
            $paddock_id = $this->response['data'][$i]['id'];
            $paddock_data = PaddockPlan::select()->where('paddock_id', '=', $paddock_id)->orderBy('start_date','desc')->limit(2)->get();
            for ($x = 0; $x < count($paddock_data); $x++){
                $paddock_plan_tasks = PaddockPlanTask::select()->where("paddock_plan_id", "=", $paddock_data[$x]['id'])->get();
                if (count($paddock_plan_tasks) > 0){
                    $paddock_data[$x]['paddock_tasks_data'] = $paddock_plan_tasks;
                }
                $crop_name = Crop::select('name')->where('id', '=', $paddock_data[$x]['crop_id'])->get();
                if (count($crop_name)>0){
                    $paddock_data[$x]['crop_name'] = $crop_name[0]['name'];
                }
            }
            $this->response['data'][$i]['paddock_data'] = $paddock_data;
        }
    }
    return $this->response();
  }

  public function retrieveWithSprayMix(Request $request){
    $data = $request->all();
    $this->model = new Paddock();
    $this->retrieveDB($data);
    for ($i = 0; $i < count($this->response['data']); $i++){
      $item = $this->response['data'][$i];

      // dd($paddock_plan_tasks);
      $this->response['data'][$i]['spray_mix'] = null;
      $this->response['data'][$i]['due_date'] = null;
      $this->response['data'][$i]['machine'] = null; // get the used machine
      $this->response['data'][$i]['start_date'] = null;
      $this->response['data'][$i]['end_date'] = null;
      $this->response['data'][$i]['crop_name'] = null;
      $paddockPlan = PaddockPlan::select()->where("paddock_id", "=", $item['id'])->orderBy('start_date','desc')->limit(1)->get();  

      if($paddockPlan){
          $this->response['data'][$i]['started'] = $paddockPlan[0]['start_date'];
          $crop = Crop::where("id", "=", $paddockPlan[0]['crop_id'])->get();
          $this->response['data'][$i]['crop_name'] = sizeof($crop) > 0 ? $crop[0]['name'] : null;
          $paddockPlanTask = PaddockPlanTask::where("paddock_plan_id", "=", $paddockPlan[0]['paddock_id'])->get(['spray_mix_id', 'id', 'paddock_plan_id', 'due_date']);
          if($paddockPlanTask && sizeof($paddockPlanTask) > 0){
            $temp = app($this->batchPaddockTaskClass)->retrieveBatchByPaddockPlanTask($paddockPlanTask[0]['id']);
            $this->response['data'][$i]['spray_mix'] = app($this->sprayMixClass)->getByParamsDefault('id', '=', $paddockPlanTask[0]['spray_mix_id'])->get();
            $this->response['data'][$i]['due_date'] = $paddockPlanTask[0]['due_date'];
            $this->response['data'][$i]['start_date'] = $temp !== null ? Carbon::createFromFormat('Y-m-d H:i:s', $temp[0]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A') : null;
          // Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');$paddockPlan[0]['start_date'];
            $this->response['data'][$i]['end_date'] = $temp !== null ? Carbon::createFromFormat('Y-m-d H:i:s', $temp[0]['updated_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A') : null;
          }
      }
      
      
      $this->response['data'][$i]['operator'] = $this->retrieveName($item['account_id']); // needs to be verified
      $this->response['data'][$i]['creator'] = $this->retrieveName($item['account_id']); // needs to be verified
    }

    return $this->response();
  }



  public function retrievePaddocksAndBatchesByStatus(Request $request){
    $data = $request->all();
    $res = array();
    $res['paddocks'] = Paddock::where("status","=",$data['status'])
                        ->where("merchant_id", "=",$data['merchant_id'])
                        ->where("deleted_at", "=", NULL)
                        ->offset($data['offset'])
                        ->limit($data['limit'])
                        ->get();
    if (count($res['paddocks'])>0){
        for ($i=0; $i<count($res['paddocks']); $i++){
            $res['paddocks'][$i]['paddock_plans'] = PaddockPlan::where("paddock_id","=",$res['paddocks'][$i]['id'])
                                                    ->where("deleted_at", "=", NULL)
                                                    ->get();
            for($x=0; $x<count($res['paddocks'][$i]['paddock_plans']); $x++){
                $res['paddocks'][$i]['paddock_plans'][$x]['crop_name'] =  Crop::select("name")->where("id", "=", $res['paddocks'][$i]["paddock_plans"][$x]['crop_id'])->get();
            }
        }
    $res['batches'] = DB::table("batches AS T1")
                        ->select("T1.notes","T1.id","T1.notes","T1.water","T2.name AS spray_mix_name","T2.id AS spray_mix_id","T3.name AS machine_name","T3.capacity")
                        ->leftJoin("spray_mixes AS T2", "T1.spray_mix_id","=","T2.id")
                        ->leftJoin("machines AS T3", "T1.machine_id", "=", "T3.id")
                        ->where("T1.status","=", $data['status'])
                        ->where("T1.merchant_id", "=", $data["merchant_id"])
                        ->limit($data['limit'])
                        ->offset($data['offset'])
                        ->get();
    $this->response['data'] = $res;
    return $this->response();
    }else{
        return $this->response();
    }
  }

  public function getByParams($column, $value, $columns){
    $result = Paddock::where($column, '=', $value)->get($columns);
    return sizeof($result) > 0 ? $result[0] : null;
  }

}
