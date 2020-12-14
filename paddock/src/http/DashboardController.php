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
        ->select("T1.id", "T1.due_date", "T2.start_date", "T2.end_date", "T3.name", "T3.short_description",  "T4.name", "T4.short_description")
        ->leftJoin('paddock_plans AS T2', 'T1.paddock_plan_id','=','T2.id')
        ->leftJoin('paddocks AS T3', 'T1.paddock_id','=','T3.id')
        ->leftJoin('spray_mixes AS T4', 'T1.spray_mix_id', '=', 'T4.id')
        ->offset($data['offset'])
        ->limit($data['limit'])
        ->distinct("T1.id")
        ->get();
        $this->response['data'] = $res;
        return $this->response();
    }
}
