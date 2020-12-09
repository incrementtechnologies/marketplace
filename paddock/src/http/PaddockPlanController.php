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
            $crop_data = Crop::select()->where('id', '=', $crop)->get();
            if (count($crop) > 0){
                $this->response['data'][$i]['crop_name'] = $crop_data[0]['name'];
            }
        }
        return $this->response();
    }
}
