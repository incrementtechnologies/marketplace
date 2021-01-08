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
        // $result = DB::table('batches as T1')
        //         ->leftJoin('machines as T2', 'T2.id', '=', 'T1.machine_id')
        //         ->leftJoin('spray_mixes as T3', 'T3.id', '=', 'T1.spray_mix_id')
        //         ->where('T1.merchant_id', '=', $data['merchant_id'])
        //         ->where('T1.spray_mix_id', '=', 'T3.id')
        //         ->where('T2.id', '=', 'T1.machine_id')
        //         ->take(3)
        //         ->orderBy('T1.created_at', 'desc')
        //         ->select('T2.name', 'T2.id', 'T3.name', 'T3.id')
        //         ->get();
        $result = DB::table('batches AS T1')
                ->select("T1.merchant_id", "T1.spray_mix_id", "T1.machine_id", "T2.id", "T4.name AS merchant_name")
                ->leftJoin("machines AS T2", "T2.id", "=", "T1.machine_id")
                ->leftJoin('spray_mixes AS T3', "T3.id", "=", "T1.spray_mix_id")
                ->leftJoin('merchants AS T4', "T4.id", "=", "T1.merchant_id")
                ->where("T1.merchant_id", "=", $data['merchant_id'])
                ->take(3)
                ->get();
        if(sizeof($result) > 0){
            $this->response['data'] = $result;
        }
        else{
            $this->response['data'] = null;
        };
        return $this->response();
    }
    // else{
    //     $this->response['data'] = null;
    // };
    // return $this->response();
//   }

  public function getByMerchantId($merchantId){
    $result = SprayMix::where('merchant_id', '=', $merchantId)->orderBy('name', 'asc')->get();
    return $result;
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
        if($data['limit'] > 0){
            $tempData = SprayMix::where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
                            ->where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
                            ->orderBy($sortKey, $sortValue)
                            ->skip($data['offset'])
                            ->take($data['limit'])
                            ->get();
        }else{
            $tempData = SprayMix::where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
                            ->where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
                            ->orderBy($sortKey, $sortValue)
                            ->get();
        }
        $res = array();
        if(sizeof($tempData) > 0){
            $i = 0;
            $getCropName = null;
            foreach ($tempData as $key) {
                // dd($key->crops);
                $getCropName = app($this->cropClass)->retrieveCrops($key->crops);
                $res[$i]['name'] = $key['name'];
                $res[$i]['id'] = $key['id'];
                $res[$i]['status'] = $key['status'];
                $res[$i]['max_rate'] = $key['maximum_rate'];
                $res[$i]['min_rate'] = $key['minimum_rate'];
                $res[$i]['application_rate'] = $key['application_rate'];
                $res[$i]['short_description'] = $key['short_description'];
                $res[$i]['types'] = $getCropName;
                $i++;

            }
            $this->response['data'] = $res;
        }
        // dd($this->response);
        return $this->response();
    }
    public function retrieveOne(Request $request){
        $data = $request->all();
        $res = SprayMix::where('id', '=', $data['id'])->get();
        $getCropName = app($this->cropClass)->retrieveCrops($res[0]['crops']);
        $res[0]['type'] = $getCropName;
        return response()->json(compact('res'));
    }

    public function getByParams($column, $value, $columns){
        $result = SprayMix::where($column, '=', $value)->get($columns);
        return sizeof($result) > 0 ? $result[0] : null;
    }

    public function getByParamsDefault($column, $value){
        $result = SprayMix::where($column, '=', $value)->get();
        return sizeof($result) > 0 ? $result[0] : null;
    }
}
