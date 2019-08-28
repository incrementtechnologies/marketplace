<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\ProductTrace;
use Illuminate\Support\Facades\Hash;
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

  public function getBalanceQty($column, $value){
    $result  = ProductTrace::where($column, '=', $value)->where('status', '=', 'open')->count();
    return $result;
  }

  public function create(Request $request){
    $data = $request->all();
    $data['nfc'] = $this->generateNFC($data['product_id'], $data);
    $data['status'] = 'open';
    $this->model = new ProductTrace();
    $this->insertDB($data);
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
    return Hash::make($id.$merchantName.$title.$batchNumber.$manufacturingDate.$link);
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

  public function linkTags(Request $request){
    //
  }
}
