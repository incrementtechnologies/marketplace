<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\TransferredProduct;
class TransferredProductController extends APIController
{
    function __construct(){
      $this->model = new TransferredProduct();
    }
}
