<?php

namespace Increment\Imarket\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Imarket\Paddock\Models\Machine;
use Carbon\Carbon;

class MachineController extends APIController
{
    //
    function __construct(){
        $this->model = new Machine();
        $this->notRequired = array();
    }    
}
