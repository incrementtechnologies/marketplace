<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Machine;
use Carbon\Carbon;

class MachineController extends APIController
{
    //
    function __construct(){
        $this->model = new Machine();
        $this->notRequired = array();
    }    
}
