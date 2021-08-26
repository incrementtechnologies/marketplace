<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Crop;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CropController extends APIController
{
    
    //
    function __construct(){
        $this->model = new Crop();
        $this->notRequired = array();
    }

    public function create(Request $request){
        $data = $request->all();
        $isExist = $this->checkIfExist($data['merchant_id'], $data['name']);
        if(!$isExist){
            $this->model = new Crop();
            $this->insertDB($data);
        return $this->response();
        }else{
            $this->response['error']['message'] = 'Crop is already existed';
            return $this->response();
        }
    }

    public function retrieve(Request $request){
        $data = $request->all();
        // $result = Crop::where('merchant_id', '=', $data['merchant_id'])->where('deleted_at', '=', null)->get();
        $this->model = new Crop();
        $this->retrieveDB($data);
        return $this->response();
    }
    
    public function retrieveCrops($id){
        $tempCrop = explode(", ", $id);
        // dd($tempCrop);
        // $object = [];
        $tempResult = [];
        foreach ($tempCrop as $key) {
            // $temp = [];
            $temp = Crop::where('id', '=', (int)$key)->select('name', 'id')->get();
            array_push($tempResult, $temp); 
            // $result = $tempResult != null ? $tempResult : null;
            // $temp['id'] = $result->isEmpty() ? null : $result[0]['id'];
            // $temp['name'] = $result->isEmpty() ? null : $result[0]['name'];
            // array_push($object, json_encode($temp));
        }
            // dd($object);

        return $tempResult;
    }

    public function retrieveCropById($id){
        $result = DB::table('crops')->where('id', '=', $id)->get();
        if(sizeof($result) > 0){
            return $result;
        }else{
            return null;
        }
    }

    public function checkIfExist($merchantId, $cropName){
        $result = Crop::where('merchant_id', '=', $merchantId)->where('name', '=', $cropName)->get();
        return sizeof($result) > 0 ? true : false;
    }

    public function retrieveCropName($id){
        $result = Crop::where('id', '=', $id)->first();
        return $result['name'];
    }
}
