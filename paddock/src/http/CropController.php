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
    
    public function retrieveCrops($id){
        $tempCrop = explode(",", $id);
        $object = [];

        foreach ($tempCrop as $key) {
            $temp = [];
            $result = Crop::where('id', '=', $key)->select('name', 'id')->get();
            $temp['id'] = $result[0]['id'];
            $temp['name'] = $result[0]['name'];
            array_push($object, json_encode($temp));
        }
        // dd($object);

        return $object;
    }
}
