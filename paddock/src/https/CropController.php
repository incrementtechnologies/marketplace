<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\Crop;
use Carbon\Carbon;

class CropController extends APIController
{
    //
    function __construct(){
        $this->model = new Crop();
        $this->notRequired = array();
    }    
}
