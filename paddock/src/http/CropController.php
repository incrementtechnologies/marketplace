<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Crop;
use Carbon\Carbon;

class CropController extends APIController
{
    
    //
    function __construct(){
        $this->model = new Crop();
        $this->notRequired = array();
    }

    public function retrieve(Request $request){
        $data = $request->all();
        $result = Crop::where('merchant_id', '=', $data['merchant_id'])->where('deleted_at', '=', null)->get();

        $this->response['data'] = $result;
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
        $result = Crop::where('id', '=', $id)->get();

        if($result > 0){
            return $result;
        }else{
            return null;
        }
    }
}
