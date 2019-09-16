<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\TransferredProduct;
use Increment\Marketplace\Jobs\TransferredProduct as TransferredProductJob;
class TransferredProductController extends APIController
{
    function __construct(){
      $this->model = new TransferredProduct();
    }

    public function create(Request $request){
      $data = $request->all();
      TransferredProductJob::dispatch($data['products'], $data['transfer_id']);
      $this->response['data'] = true;
      return $this->response();
    }
}
