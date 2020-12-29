<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Carbon\Carbon;

class PaddockPlanTaskController extends APIController
{
  
  public $paddockClass = 'Increment\Marketplace\Paddock\Http\PaddockController';
  public $sprayMixClass = 'Increment\Marketplace\Paddock\Http\SprayMixController';

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
    $this->model = new PaddockPlanTask();
    $this->retrieveDB($data);
    for ($i=0; $i < count($this->response['data']); $i++){
      $item = $this->response['data'][$i];
      $this->response['data'][$i]['paddock'] = app($this->paddockClass)->getByParams('id', $item['paddock_id'], ['id', 'name']);
      $this->response['data'][$i]['spray_mix'] = app($this->sprayMixClass)->getByParams('id', $item['paddock_id'], ['id', 'name']);
    }
    return $this->response();
  }
}
