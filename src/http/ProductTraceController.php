<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\ProductTrace;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
class ProductTraceController extends APIController
{

  public $productController = 'Increment\Marketplace\Http\ProductController';
  function __construct(){
  	$this->model = new ProductTrace();

    $this->notRequired = array(
      'rf', 'nfc'
    );
  }

  public function getByParams($column, $value){
    $result  = ProductTrace::where($column, '=', $value)->orderBy('created_at', 'desc')->limit(5)->get();
    return sizeof($result) > 0 ? $result : null;
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $product = app($this->productController)->getByParams('code', $data['code']);

    if($product != null){
      $data['condition'][] = array(
        'column'  => 'product_id',
        'clause'  => '=',
        'value'   => $product['id']
      );
    }

    $this->model = new ProductTrace();
    $this->retrieveDB($data);

    $i = 0;
    foreach ($this->response['data'] as $key) {
      $item = $this->response['data'][$i];
      // $this->response['data'][$i]['manufacturing_date_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $item['manufacturing_date'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
      $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $item['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y h:i A');
      $i++;
    }
    return $this->response();
  }

  public function retrieveByParams(Request $request){
    $data = $request->all();
    $this->model = new ProductTrace();
    $this->retrieveDB($data);
    $i = 0;
    foreach ($this->response['data'] as $key) {
      $item = $this->response['data'][$i];
      $this->response['data'][$i]['product'] = app($this->productController)->getByParams('id', $item['product_id']);
      $i++;
    }
    return $this->response();
  }

  public function getByParamsDetails($column, $value){
    $result  = ProductTrace::where($column, '=', $value)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $item = $result[$i];
        $result[$i]['product'] = app($this->productController)->getByParams('id', $item['product_id']);
        $i++;
      }
    }
    return sizeof($result) > 0 ? $result : null;
  }

  public function getBalanceQty($column, $value){
    $result  = ProductTrace::where($column, '=', $value)->where('status', '=', 'open')->count();
    return $result;
  }

  public function create(Request $request){
    $data = $request->all();
    $qty = intval($data['qty']);
    for ($i=0; $i < $qty; $i++) { 
      $data['nfc'] = $this->generateNFC($data['product_id'], $data);
      $data['code'] = $this->generateCode();
      $data['status'] = 'open';
      $this->model = new ProductTrace();
      $this->insertDB($data);
    }
    return $this->response();
  }

  public function generateNFC($productId, $data){
    $product = app($this->productController)->retrieveProductById($productId, $data['account_id'], $data['inventory_type']);
    // product trace code
    $id = $product['code'].'/0/';
    // $merchantName = $product['merchant']['name'].'/0/';
    // $title = $product['title'].'/0/';
    $batchNumber = $data['batch_number'].'/0/';
    $manufacturingDate = $data['manufacturing_date'].'/0/';
    // $link = 'https://www.traceag.com.au/product/'.$product['code'].'/0/';
    return Hash::make($id.$batchNumber.$manufacturingDate);
    // product id
    // trace id
    // merchant name
    // product title
    // payload
    // batch number
    // manufacturing date
    // website
    // delimiter = 0
    // generate code for nfc
  }

  public function generateCode(){
    $code = substr(str_shuffle("0123456789012345678901234567890123456789"), 0, 32);
    $codeExist = ProductTrace::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }
  
  public function linkTags(Request $request){
    //
  }
}
