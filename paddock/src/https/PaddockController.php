<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\Paddock;
use Carbon\Carbon;

class PaddockController extends APIController
{
    //
    function __construct(){
        $this->model = new Paddock();
        $this->notRequired = array(
            'note'
        );
    }    
}
