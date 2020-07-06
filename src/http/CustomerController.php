<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Customer;
use Carbon\Carbon;
class CustomerController extends APIController
{
  function __construct(){
    $this->model = new Customer();
  }
}
