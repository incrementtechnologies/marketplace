<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Product;
use Carbon\Carbon;
class MarketplaceController extends APIController
{
  function __construct(){
    $this->model = new Product();
  }
}
