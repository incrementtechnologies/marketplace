<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\SprayMixProduct;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SprayMixProductController extends APIController
{
    //
    function __construct(){
        $this->model = new SprayMixProduct();
        $this->notRequired = array();
    }

    public function retrieveBasedOnStatus(Request $request){
        $data = $request->all();
        $result = DB::table("spray_mix_products AS T1")
                    ->select("T1.rate","T1.status","T1.created_at AS spray_mix_prod_created", "T2.title AS product_name", "T3.application_rate", "T3.minimum_rate", "T3.maximum_rate", "T3.name AS spray_mix_name")
                    ->leftJoin("products AS T2", "T1.product_id", "=", "T2.id")
                    ->leftJoin("spray_mixes AS T3", "T1.spray_mix_id", "=", "T3.id")
                    ->where("T1.id", "=", $data['id'])
                    ->get();
        $this->response['data'] = $result;
        return $this->response();
    }
}
