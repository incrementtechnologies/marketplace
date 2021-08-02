<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlan;
use Increment\Marketplace\Paddock\Models\Crop;
use Carbon\Carbon;

class PaddockPlanController extends APIController
{
    //
    function __construct(){
        $this->model = new PaddockPlan();
        $this->notRequired = array();
    }
    
    public function retrieve(Request $request){
        $data = $request->all();
        $this->model = new PaddockPlan();
        $this->retrieveDB($data);
        for ($i=0; $i<count($this->response['data']); $i++){
            $crop = $this->response['data'][$i]['crop_id'];
            $crop_data = Crop::select()->where('id', '=',  $crop)->get();
            if (count($crop_data) != 0){
                $this->response['data'][$i]['crop_name'] = $crop_data[0]['name'];
            }
            $this->response['data'][$i]['start_date'] = Carbon::createFromFormat('Y-m-d', $this->response['data'][$i]['start_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
            $this->response['data'][$i]['end_date'] = Carbon::createFromFormat('Y-m-d', $this->response['data'][$i]['end_date'])->copy()->tz($this->response['timezone'])->format('d/m/Y');
        }
        return $this->response();
    }

    public function retrievePaddockPlanById($paddockPlanId){
        $result = PaddockPlan::where('id', '=', $paddockPlanId)->get();
        if(sizeof($result) > 0){
            return $result;
        }else{
            return null;
        }
    }

    public function create(Request $request) {
      $data = $request->all();
      $paddockPlan = new PaddockPlan;
      $paddockPlan->paddock_id = $data['paddock_id'];
      $paddockPlan->crop_id = $data['crop_id'];
      $paddockPlan->start_date = $data['start_date'];
      $paddockPlan->end_date = $data['end_date'];
      $paddockPlan->save();
      $this->response['data'] = $paddockPlan;
      return $this->response();
    }

    public function retrievePlanByParams($column, $value, $returns){
        $result = PaddockPlan::where($column, '=', $value)->where('deleted_at', '=', null)->get($returns);
        if(sizeof($result) > 0){
            return $result;
        }else{
            return null;
        } 
    }
}
