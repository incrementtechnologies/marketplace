<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Carbon\Carbon;

class PaddockPlanTaskController extends APIController
{
    //
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
             $this->response['data'][$i]['spray_mix_name'] = $spraymixdata[0]['name'];
        }
        return $this->response();
    }
}
