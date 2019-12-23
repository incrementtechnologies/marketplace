<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\TransferredProduct;
use Carbon\Carbon;
class TransferredProductController extends APIController
{

    public $productTraceController = 'Increment\Marketplace\Http\ProductTraceController';
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
            'product_id'    => $data['products'][$i]['product_id'],
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

    public function retrieve(Request $request){
      $data = $request->all();

      $this->model = new TransferredProduct();
      $this->retrieveDB($data);
      $result = $this->response['data'];
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['product_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['payload_value']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
          $i++;
        }
      }

      return $this->response();
    }

    public function getByParams($column, $value){
      $result = TransferredProduct::where($column, '=', $value)->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['product_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['payload_value']);
          $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
          $i++;
        }
      }
      return sizeof($result) > 0 ? $result : null;
    }

    public function getByParamsOnly($column, $value){
      $result = TransferredProduct::where($column, '=', $value)->get();
      return sizeof($result) > 0 ? $result : null;
    }

    public function insert($data){
      TransferredProduct::insert($data);
      return true;
    }

    public function getSize($column, $value, $date){
      $result = TransferredProduct::where($column, '=', $value)->where('created_at', '>', $date)->count();
      return $result;
    }


}
