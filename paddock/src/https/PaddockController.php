<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\PaddockPlan;
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
        $this->model = new Paddock();
        $this->retrieveDB($data);
        for ($i = 0; $i < count($this->response['data']); $i++){
            $paddockData = $this->response['data'];
            $paddock_id = $this->response['data']['0']['id'];
            $paddock_data = PaddockPlan::select()->where('paddock_id', '=', $paddock_id)->orderBy('created_at','desc')->limit(2)->get();
            $this->response['data'][$i]['paddock_data'] = $paddock_data;
        }
        return $this->response();
    }
}
