<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Transfer;
use Carbon\Carbon;
class TransferController extends APIController
{
   public $transferredProductsClass = 'Increment\Marketplace\Http\TransferredProductController';
   public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
    function __construct(){
      $this->model = new Transfer();
    }

    public function create(Request $request){
      $data = $request->all();
      $data['code'] = $this->generateCode();
      $this->insertDB($data);
      return $this->response();
    }
    
    public function generateCode(){
      $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32);
      $codeExist = Transfer::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function retrieve(Request $request){
      $data = $request->all();

      $this->model = new Transfer();
      $this->retrieveDB($data);
      $result = $this->response['data'];
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['transferred_products'] = app($this->transferredProductsClass)->getByParams('transfer_id', $result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
          $this->response['data'][$i]['to_details'] = app($this->merchantClass)->getByParams('id', $result[$i]['to']);
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $i++;
        }
      }

      return $this->response();
    }
}
