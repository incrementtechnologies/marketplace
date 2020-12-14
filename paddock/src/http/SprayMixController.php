<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Increment\Marketplace\Paddock\Models\SprayMixProduct;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SprayMixController extends APIController
{
    //
    function __construct(){
        $this->model = new SprayMix();
        $this->notRequired = array();
    }
    
    public function retrieveDetails(Request $request){
        $data = $request->all();
        $res = DB::table('spray_mixes AS T1')
        ->select("T1.id", "T1.name AS spray_mix_name", "T1.short_description", "T1.crops", "T1.minimum_rate", "T1.maximum_rate", "T2.rate", "T3.title AS product_title", "T3.description AS product_description")
        ->leftJoin("spray_mix_products AS T2", "T1.id", '=', "T2.spray_mix_id")
        ->leftJoin("products AS T3", "T2.product_id", '=', "T3.id")
        ->offset($data['offset'])
        ->limit($data['limit'])
        ->where("T1.merchant_id", "=", $data['merchant_id'])
        ->distinct("T1.id")
        ->get();
        $this->response['data'] = $res;
        return $this->response();
    }
}
