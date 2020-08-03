<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\OrderRequest;
use Carbon\Carbon;
class OrderRequestController extends APIController
{
  function __construct(){
    $this->model = new OrderRequest();
  }
}
