<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\Batch;
use Increment\Marketplace\Paddock\Models\PaddockPlan;
use Increment\Marketplace\Paddock\Models\Crop;
use Illuminate\Support\Facades\DB;
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
                    ->where("merchant_id", "=", $data['merchant_id'])
                    ->where("status", "=", $data['status'])
                    ->get();
            $result['paddock_data'] = PaddockPlan::select()->where("paddock_id", "=", $data['id'])->orderBy('start_date','desc')->limit(2)->get();
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
                for($x=0; $x<count($res['paddocks'][$i]['paddock_data']); $x++){
                    $res['paddock_data'][$i]['paddock_plans'][$x]['crop_name'] =  Crop::select("name")->where("id", "=", $result['paddock_data'][$i]["paddock_plans"][$x]['crop_id'])->get();
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
}
