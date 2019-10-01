<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\BundledProduct;
class BundledProductController extends APIController
{
  function __construct(){
    $this->model = new BundledProduct();
  }
}
