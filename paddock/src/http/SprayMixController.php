<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SprayMixController extends APIController
{
    //
    function __construct(){
        $this->model = new SprayMix();
        $this->notRequired = array();
    }


    public function retrieveRescent(Request $request){
        $data = $request->all();

        $result = DB::table('batches as T1')
                -> join('machines as T2', 'T2.id', '=', 'T1.machine_id')
                ->join('spray_mixes as T3', 'T3.id', '=', 'T1.spray_mix_id')
                ->where('T1.merchant_id', '=', $data['merchant_id'])
                ->where('T1.spray_mix_id', '=', 'T3.id')
                ->where('T2.id', '=', 'T1.machine_id')
                ->take(3)
                ->orderBy('T1.created_at', 'desc')
                ->select('T2.name', 'T2.id', 'T3.name', 'T3.id')
                ->get();

        if(sizeof(result) > 0){
            $this->response['data'] = $result;
        }
        else{
            $this->response['data'] = null;
        };
        return $this->response();
    }
}
