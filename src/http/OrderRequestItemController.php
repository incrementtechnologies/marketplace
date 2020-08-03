<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\OrderRequestItem;
use Carbon\Carbon;
class OrderRequestItemController extends APIController
{
  function __construct(){
    $this->model = new OrderRequestItem();
  }
}
