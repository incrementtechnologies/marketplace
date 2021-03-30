<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\BundledSetting;
use Increment\Marketplace\Models\BundledProduct;
use Increment\Marketplace\Models\Product;
use Carbon\Carbon;
class BundledSettingController extends APIController
{
  
  public $productController = 'Increment\Marketplace\Http\ProductController';
  public $productTraceController = 'Increment\Marketplace\Http\ProductTraceController';
  public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
  public $productAttrController = 'Increment\Marketplace\Http\ProductAttributeController';

  function __construct(){
    $this->model = new BundledSetting();
  }


  public function create(Request $request){
    $data = $request->all();

    $result = BundledSetting::where('bundled', '=', $data['bundled'])->where('product_id', '=', $data['product_id'])->where('deleted_at', '=', null)->get();
    if(sizeof($result) > 0){
      $this->response['data'] = null;
      $this->response['error'] = 'Already existed!';
    }else{
      $this->model = new BundledSetting();
      $this->insertDB($data);
      $bundled = BundledSetting::where('bundled', '=', $data['bundled'])->where('deleted_at', '=', null)->get();
      if(sizeof($bundled) > 1){
        Product::where('id', '=', $data['bundled'])->where('status', '=', 'pending')->update(array(
          'type' => 'custom_bundled'
        ));
      }
    }
    return $this->response();
  }


  public function retrieve(Request $request){
    $data = $request->all();
    $this->model = new BundledSetting();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['product'] = app($this->productController)->getByParams('id', $result[$i]['product_id']);
        $this->response['data'][$i]['variation'] = app($this->productAttrController)->getByParams('id', $result[$i]['product_attribute_id']);
        $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
        $remainingQty = intval($result[$i]['qty']);
        $this->response['data'][$i]['remaining_qty'] = $remainingQty;
        $i++;
      }
    }
    $this->response['status'] = 1;
    return $this->response();
  }

  public function retrieveWithTrace(Request $request){
    $data = $request->all();
    $this->model = new BundledSetting();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    $status = 1;
    $bundled = app($this->productTraceController)->getByParamsDetails('id', $data['bundled_trace']);
    $bundled = sizeof($bundled) > 0 ? $bundled[0] : null;
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['product'] = app($this->productController)->getByParams('id', $result[$i]['product_id']);
        $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
        $qtyAdded = app($this->bundledProductController)->getRemainingQty($data['bundled_trace'], $result[$i]['product_id']);
        $remainingQty = intval($result[$i]['qty']) - intval($qtyAdded);
        $this->response['data'][$i]['remaining_qty'] = $remainingQty;
        if($status == 1 && $remainingQty > 0){
          $status = 0;
        }
        $i++;
      }
    }
    $status = $bundled != null && $bundled['rf'] != null ? 2 : $status;
    $this->response['status'] = $status;
    return $this->response();
  }

  public function delete(Request $request){
    $data = $request->all();
    $deleteData = array(
      'id' => $data['id']
    );
    $this->deleteDB($data);
    $bundled = BundledSetting::where('bundled', '=', $data['bundled'])->where('deleted_at', '=', null)->get();
    if(sizeof($bundled) == 1 || sizeof($bundled) == 0){
      Product::where('id', '=', $data['bundled'])->where('status', '=', 'pending')->update(array(
        'type' => 'bundled'
      ));
    }
    return $this->response();
  }

  public function getStatusByProductTrace($bundled, $bundledTrace){
    $result = BundledSetting::where('bundled', '=', $bundled)->get();
    $status = 1;
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $qtyAdded = app($this->bundledProductController)->getRemainingQty($bundledTrace, $result[$i]['product_id']);
        $remainingQty = intval($result[$i]['qty']) - $qtyAdded;
        $this->response['data'][$i]['remaining_qty'] = $remainingQty;
        if($status == 1 && $remainingQty > 0){
          $status = 0;
        }
        $i++;
      }
    }
    return $status;
  }


  public function getByParams($column, $value, $merchantId){
    $this->localization();
    $result = BundledSetting::where($column, '=', $value)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $traceQty = app($this->productTraceController)->getProductQtyByParams($result[$i]['bundled'], $result[$i]['product_attribute_id']);
        // $result[$i]['product'] = app($this->productController)->getByParamsWithReturn('id', $result[$i]['product_id'], ['title', 'id', 'tags']);
        $result[$i]['variation'] = app($this->productAttrController)->getByParamsWithMerchant('id', $result[$i]['product_attribute_id'], $merchantId);
        $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
        $result[$i]['qty'] = (int)$result[$i]['qty'];
        $result[$i]['scanned_qty'] = (int)$traceQty == (int)$result[$i]['qty'] ? 1 : 0;
        $i++;
      }
    }
    return sizeof($result) > 0 ? $result : null;
  }

  public function getByParamsDetails($column, $value){
    $result = BundledSetting::where($column, '=', $value)->get();
    return sizeof($result) > 0 ? $result : null;
  }
}
