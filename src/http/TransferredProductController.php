<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\TransferredProduct;
use Carbon\Carbon;
class TransferredProductController extends APIController
{
    function __construct(){
      $this->model = new TransferredProduct();
    }

    public function create(Request $request){
      $data = $request->all();
      if(sizeof($data['products']) > 0){
        $array = array();
        for ($i=0; $i < sizeof($data['products']); $i++) {
          $array[] = array(
            'transfer_id' => $data['transfer_id'],
            'payload'     => 'product_traces',
            'payload_value' => $data['products'][$i]['id'],
            'created_at'    => Carbon::now()
          );
        }
        TransferredProduct::insert($array);
        $this->response['data'] = true;
      }else{
        $this->response['data'] = false;
      }
      return $this->response();
    }

    public function getByParams($column, $value){
      $result = TransferredProduct::where($column, '=', $value)->get();
      return sizeof($result) > 0 ? $result : null;
    }


}
