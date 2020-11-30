<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Batch;
use Carbon\Carbon;

class BatchController extends APIController
{
    //
    function __construct(){
        $this->model = new Batch();
        $this->notRequired = array(
            'spray_mix_id','machine_id','notes'
        );
    }    
}
