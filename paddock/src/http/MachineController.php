<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Machine;
use Increment\Marketplace\Paddock\Models\Batch;
use Carbon\Carbon;

class MachineController extends APIController
{
    //
    function __construct(){
        $this->model = new Machine();
        $this->notRequired = array();
    }    

    public function retrieve(Request $request){
        $data = $request->all();
        $this->model = new Machine();
        $this->retrieveDB($data);
        for ($i = 0; $i < count($this->response['data']); $i++){
            $machine_id = $this->response['data'][$i]['id'];
            $paddock_data = Batch::select()->where('machine_id', '=', $machine_id)->get();
            if (count($paddock_data)>0){
                $this->response['data'][$i]['used_status'] = true;
            }else{
                $this->response['data'][$i]['used_status'] = false;
            }
        }
        return $this->response();
    }

    public function create(Request $request){
        $data = $request->all();
        $this->model = new Machine();
        $uniqueVerif = Machine::select()
        ->where('merchant_id', '=', $data['merchant_id'])
        ->where('name', '=', $data['name'])
        ->get();
        if (count($uniqueVerif) > 0){
            $this->response['error'] = 'Duplicate machine name for merchant_id';
            return $this->response();
        }else{
            $this->insertDB($data);
            return $this->response();
        }
    }
    
    public function getByMerchantId($merchantId){
      $result = Machine::where('merchant_id', '=', $merchantId)->orderBy('name', 'asc')->get(['name', 'id']);
      return $result;
    }
}
