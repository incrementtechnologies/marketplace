<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\PaddockPlan;
use Increment\Marketplace\Paddock\Models\Crop;
use Carbon\Carbon;

class PaddockController extends APIController
{
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
                    ->where("status", "=", $data['status'])
                    ->get();
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
}
