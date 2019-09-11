<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Transfer;
class TransferController extends APIController
{
    function __construct(){
      $this->model = new Transfer();
    }
}
