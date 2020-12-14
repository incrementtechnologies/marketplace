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
    public $cropClass = 'Increment\Marketplace\Paddock\Http\CropController';
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

    public function retrieve(Request $request){
        $data = $request->all();
        $con = $data['condition'];
        $sortKey = null;
        $sortValue = null;
        foreach ($data['sort'] as $key) {
            $sortKey = array_keys($data['sort'])[0];
            $sortValue = $key;
        }
        $tempData = SprayMix::where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
                            ->where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
                            ->orderBy($sortKey, $sortValue)
                            ->skip($data['offset'])
                            ->take($data['limit'])
                            ->get();
        
        $res = array();
        if(sizeof($tempData) > 0){
            $i = 0;
            $getCropName = null;
            foreach ($tempData as $key) {
                $getCropName = app($this->cropClass)->retrieveCrops($key->crops);
                $res[$i]['type'] = $getCropName;
                $res[$i]['name'] = $key['name'];
                $res[$i]['id'] = $key['id'];
                $res[$i]['status'] = $key['status'];
                $i++;

            }
            $this->response['data'] = $res;
        }
        return $this->response();
    }


}
