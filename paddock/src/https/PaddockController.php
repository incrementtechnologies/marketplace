<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\Paddock;
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
