<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\MerchantProduct;
use Carbon\Carbon;

class MerchantProductController extends APIController
{
    //
    public function checkIfExist($column, $value){
        $res = MerchantProduct::where($column, '=', $value)->get();
        return sizeof($res) > 0 ? true : false;
    }

    public function insertToDB($data){
        $res = MerchantProduct::create($data);
        return $res;
    }
}
