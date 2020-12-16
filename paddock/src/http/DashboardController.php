<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends APIController
{

    public function retrieveDashboard(Request $request){
        $data = $request->all();
        $res = array();
        $res['infocus'] = DB::table('paddock_plans_tasks as T1')
        ->select("T1.id", "T1.due_date", "T2.start_date", "T2.end_date", "T3.name", "T3.short_description", "T3.name AS payload", "T4.name", "T4.short_description")
        ->leftJoin('paddock_plans AS T2', 'T1.paddock_plan_id','=','T2.id')
        ->leftJoin('paddocks AS T3', 'T1.paddock_id','=','T3.id')
        ->leftJoin('spray_mixes AS T4', 'T1.spray_mix_id', '=', 'T4.id')
        ->offset($data['offset'])
        ->limit($data['limit'])
        ->where('T3.merchant_id', '=', $data['merchant_id'])
        ->distinct("T1.id")
        ->get();
        $this->response['data'] = $res;
        return $this->response();
    }


    public function retrieveDashboardBatches(Request $request){
        $data = $request->all();
        $res = array();
        $res['infocus'] = DB::table("batches AS T1")
        ->select("T1.id AS batch_id", "T1.account_id", "T1.notes", "T1.created_at AS created_at_human", "T6.name",  "T7.due_date", "T8.name AS paddock_name")
        ->leftJoin("batch_paddock_tasks AS T2", "T1.id", "=", "T2.batch_id")
        ->leftJoin("batch_products AS T3", "T1.id", "=", "T3.batch_id")
        ->leftJoin("spray_mixes AS T4", "T1.spray_mix_id", "=", "T4.id")
        ->leftJoin("machines AS T5", "T1.machine_id", "=", "T5.id")
        ->leftJoin("merchants AS T6", "T1.merchant_id", "=", "T6.id")
        ->leftJoin("paddock_plans_tasks AS T7", "T2.paddock_plan_task_id", "=", "T7.id")
        ->leftJoin("paddocks AS T8", "T7.paddock_id","=","T8.id") 
        ->where("T1.merchant_id", "=", $data['merchant_id'])
        ->take(3)  
        ->get();
        $res['recent'] = DB::table('batches AS T1')
        ->select("T1.merchant_id", "T1.spray_mix_id", "T1.machine_id", "T2.id", "T4.name AS merchant_name")
        ->leftJoin("machines AS T2", "T2.id", "=", "T1.machine_id")
        ->leftJoin('spray_mixes AS T3', "T3.id", "=", "T1.spray_mix_id")
        ->leftJoin('merchants AS T4', "T4.id", "=", "T1.merchant_id")
        ->where("T1.merchant_id", "=", $data['merchant_id'])
        ->take(4)
        ->get();
        if(sizeof($res['infocus']) > 0){
            $this->response['data'] = $res;
        }
        else{
            $this->response['data'] = null;
        };
        return $this->response();
    }
}
