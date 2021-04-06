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
  public $date;
  public function retrieve(Request $request){
    $data = $request->all();
    // dd($data);
    if (isset($data['id'])) {
        $result = Paddock::where("id", "=", $data['id'])
                ->where("merchant_id", "=", $data['merchant_id'])
                ->where("status", "=", $data['status'])
                ->where('deleted_at', '=', null)
                ->get();
        $result['area'] = $result['area'];
        $result['unit'] = 'Ha';
        $result['paddock_data'] = PaddockPlan::select()->where("paddock_id", "=", $data['id'])->orderBy('start_date','desc')->limit(2)->get();
        $paddock_plan_tasks = PaddockPlanTask::select()->where("paddock_plan_id", "=", $result['paddock_data'][0]['id'])->get();
        $result['paddock_data'][0]['paddock_tasks_data'] = $paddock_plan_tasks;
        for($i=0; $i<count($result['paddock_data']); $i++){
            $result['paddock_data'][$i]['crop_name'] =  Crop::select("name")->where("id", "=", $result['paddock_data'][$i]['crop_id'])->get();
        }
        $this->response['data'] = $result;
    }else{
      $data = $request->all();
      if($data['condition'][0]['column'] === 'date') {
        if($data['condition'][0]['value'] == '%%') {
          $this->date = Carbon::now()->format('Y-m-d');
        } else {
          $this->date = date(substr($data['condition'][0]['value'], 1, -1));
        }
        $result = Paddock::where($data['condition'][1]['column'], $data['condition'][1]['clause'], $data['condition'][1]['value'])
          ->where($data['condition'][2]['column'], $data['condition'][2]['clause'], $data['condition'][2]['value'])
          ->skip($data['offset'])
          ->take($data['limit'])
          ->get();
          $this->response['data'] = $result;
        
          for ($i = 0; $i < count($this->response['data']); $i++){
            $paddockData = $this->response['data'];
            $paddock_id =$this->response['data'][$i]['id'];
            $paddock_data = PaddockPlan::select()
              ->where('paddock_id', '=', $paddock_id)
              ->where('start_date', '<=', $this->date)
              ->limit(1)
              ->get();
              // dd($paddock_data);
            for ($x = 0; $x < count($paddock_data); $x++){
              $paddock_plan_tasks = PaddockPlanTask::select()->where("paddock_plan_id", "=", $paddock_data[$x]['id'])->get();
              for ($p = 0; $p < count($paddock_plan_tasks); $p++){
                if($paddock_plan_tasks[$p]['status'] === 'approved'){
                  $this->response['data'][$i]['status'] = 'approved';
                }
              }
              $this->response['data'][$x]['area'] = $this->response['data'][$x]['area'];
              $this->response['data'][$x]['unit'] = 'Ha' ;
                if (count($paddock_plan_tasks) > 0){
                    $paddock_data[$x]['paddock_tasks_data'] = $paddock_plan_tasks;
                }
                $crop_name = Crop::select('name')->where('id', '=', $paddock_data[$x]['crop_id'])->get();
                if (count($crop_name)>0){
                    $paddock_data[$x]['crop_name'] = $crop_name[0]['name'];
                }
                if(date($paddock_data[$x]['end_date']) >= $this->date) {
                  $this->response['data'][$i]['paddock_data'] = $paddock_data;
                } else {
                  $this->response['data'][$i]['paddock_data'] = [];
                }
            }
        }
      } else {
        $this->model = new Paddock();
        $this->retrieveDB($data);
        for ($i = 0; $i < count($this->response['data']); $i++){
          $paddockData = $this->response['data'];
          $paddock_id = $this->response['data'][$i]['id'];
          $paddock_data = PaddockPlan::select()
          ->where('paddock_id', '=', $paddock_id)
          ->where('start_date', '<=', count($data['condition']) === 1 ? date($data['date']) : Carbon::now()->format('Y-m-d'))
          ->orderBy('start_date','desc')
          ->limit(1)
          ->get();
          if(count($paddock_data) > 0 && date($paddock_data[0]['end_date']) >= Carbon::now()->format('Y-m-d')) {
            $paddock_plan_tasks = PaddockPlanTask::select()->where("paddock_plan_id", "=", $paddock_data[0]['id'])->get();
            for ($p = 0; $p < count($paddock_plan_tasks); $p++){
              if($paddock_plan_tasks[$p]['status'] === 'approved'){
                $this->response['data'][$i]['status'] = 'approved';
              }
            }
            $this->response['data'][$i]['area'] = $this->response['data'][$i]['area'];
            $this->response['data'][$i]['unit'] = 'Ha' ;
            if (count($paddock_plan_tasks) > 0){
                $paddock_data[0]['paddock_tasks_data'] = $paddock_plan_tasks;
            }
            $crop_name = Crop::select('name')->where('id', '=', $paddock_data[0]['crop_id'])->get();
            if (count($crop_name)>0){
                $paddock_data[0]['crop_name'] = $crop_name[0]['name'];
            }
            $this->response['data'][$i]['paddock_data'] = $paddock_data;
          } else {
            $this->response['data'][$i]['paddock_data'] = [];
          }
        }
      }
    }
    return $this->response();
  }

  public function retrieveWithSprayMix(Request $request){
    $data = $request->all();
    $this->model = new Paddock();
    $this->retrieveDB($data);
    $result = Paddock::where('id', '=', (int)$data['condition'][0]['value'])->get();
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
        $this->response['data'][$i]['area'] = (int)$this->response['data'][$i]['area'];
        $this->response['data'][$i]['arable_area'] = (int)$this->response['data'][$i]['arable_area'];
        $this->response['data'][$i]['units'] = 'Ha';
        $this->response['data'][$i]['spray_area'] = (int)$this->response['data'][$i]['spray_area'];
        $this->response['data'][$i]['started'] = $paddockPlan[0]['start_date'];
        $crop = Crop::where("id", "=", $paddockPlan[0]['crop_id'])->get();
        $this->response['data'][$i]['crop_name'] = sizeof($crop) > 0 ? $crop[0]['name'] : null;
        $paddockPlanTask = PaddockPlanTask::where("paddock_plan_id", "=", $paddockPlan[0]['id'])->get();
        if($paddockPlanTask && sizeof($paddockPlanTask) > 0){
          $temp = app($this->batchPaddockTaskClass)->retrieveBatchByPaddockPlanTask($paddockPlanTask[0]['id']);
          $this->response['data'][$i]['spray_mix'] = app($this->sprayMixClass)->getByParamsDefault('id', $paddockPlanTask[0]['spray_mix_id']);
          $this->response['data'][$i]['due_date'] = $paddockPlanTask[0]['due_date'];
          $this->response['data'][$i]['start_date'] = $temp !== null ? Carbon::createFromFormat('Y-m-d H:i:s', $temp['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A') : null;
        // Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');$paddockPlan[0]['start_date'];
          $this->response['data'][$i]['end_date'] = $temp !== null ? Carbon::createFromFormat('Y-m-d H:i:s', $temp['updated_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A') : null;
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
    $result = Paddock::where($column, '=', $value)->groupBy('id')->get($columns);
    return sizeof($result) > 0 ? $result[0] : null;
  }

}
