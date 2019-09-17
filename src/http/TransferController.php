<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Transfer;
use Carbon\Carbon;
class TransferController extends APIController
{
   public $transferredProductsClass = 'Increment\Marketplace\Http\TransferredProductController';
    function __construct(){
      $this->model = new Transfer();
    }

    public function retrieve(Request $request){
      $data = $request->all();

      $this->model = new Transfer();
      $this->retrieveDB($data);
      $result = $this->response('data');
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['transferred_products'] = app($this->transferredProductsClass)->getByParams('transfer_id', $result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
          $i++;
        }
      }

      return $this->response();
    }
}
