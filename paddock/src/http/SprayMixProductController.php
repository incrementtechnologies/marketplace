<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\SprayMixProduct;
use Carbon\Carbon;

class SprayMixProductController extends APIController
{
    //
    function __construct(){
        $this->model = new SprayMixProduct();
        $this->notRequired = array();
    }
}
