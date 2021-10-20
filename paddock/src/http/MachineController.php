<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Machine;
use Increment\Marketplace\Paddock\Models\Batch;
use Illuminate\Support\Facades\DB;
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
        $con = $data['condition'];
        // dd($con[1]['column'], $con[1]['clause'], $con[1]['value']);
        // $this->model = new Machine();
        // $this->retrieveDB($data);
        $result = DB::table('machines')
            ->Where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
            ->Where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
            ->whereNull('deleted_at')
            ->skip($data['offset'])->take($data['limit'])
            ->orderBy(array_keys($data['sort'])[0], array_values($data['sort'])[0])
            ->get();
        $temp = json_decode(json_encode($result), true);
        $this->response['data'] = $temp;
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
      $result = Machine::where('merchant_id', '=', $merchantId)->orderBy('updated_at', 'asc')->get();
      return $result;
    }

    public function getMachineNameByParams($column, $value){
        $result = Machine::where($column, '=', $value)->select('name')->get();
        return $result;
    }

    public function getMachineByParams($column, $value){
        return Machine::where($column, '=', $value)->first();
    }

    public function delete(Request $request){
        $data = $request->all();
        $res = Machine::where('id', '=', $data['id'])->update(array(
            'deleted_at' => Carbon::now(),
            'status' => $data['status']
        ));
        $this->response['data'] = $res;
        return $this->response();
    }
}
