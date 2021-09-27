<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\BundledSetting;
use Increment\Marketplace\Models\BundledProduct;
use Increment\Marketplace\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class BundledSettingController extends APIController
{
  
  public $productController = 'Increment\Marketplace\Http\ProductController';
  public $productTraceController = 'Increment\Marketplace\Http\ProductTraceController';
  public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
  public $productAttrController = 'Increment\Marketplace\Http\ProductAttributeController';
  public $transferredProductController = 'Increment\Marketplace\Http\TransferredProductController';

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
    $result = BundledSetting::where('bundled', '=', $bundled)->where('deleted_at', '=', null)->get();
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
    $result =  DB::table('bundled_settings')->where($column, '=', $value)->get();
    // BundledSetting::where($column, '=', $value)->where('deleted_at', '=', null)->orWhere('deleted_at', '!=', null)->get();
    $res = array();
    if(sizeof($result) > 0){
      $result = json_decode(json_encode($result), true);
      $i = 0;
      foreach ($result as $key) {
        $bundledProduct = DB::table('products as T1')->where('T1.id', '=', $key['bundled'])->where('T1.deleted_at' , '=', null)->get();
        $traceQty = app($this->productTraceController)->getProductQtyByParams($result[$i]['bundled'], $result[$i]['product_attribute_id']);
        $totalTransferredBundled = app($this->transferredProductController)->getTotalTransferredBundledProducts($result[$i]['bundled']);
        // dd($traceQty, sizeOf($totalTransferredBundled)); 
        // $result[$i]['product'] = app($this->productController)->getByParamsWithReturn('id', $result[$i]['product_id'], ['title', 'id', 'tags']);
        $result[$i]['variation'] = app($this->productAttrController)->getByParamsWithMerchant('id', $result[$i]['product_attribute_id'], $merchantId);
        $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
        $result[$i]['qty'] = (int)$result[$i]['qty'];
        $result[$i]['scanned_qty'] = $traceQty - sizeOf($totalTransferredBundled);
        $result[$i]['is_transferred'] = false;
        if(sizeof($bundledProduct) > 0){
          array_push($res, $result[$i]);
        }
        $i++;
      }
    }
    return sizeof($res) > 0 ? $res : [];
  }

  public function getByParamsWithProduct($column, $value, $merchantId){
    $this->localization();
    $result = DB::table('bundled_settings')->where($column, '=', $value)->get();
    $res = array();
    if(sizeof($result) > 0){
      $i = 0;
      $result = json_decode(json_encode($result), true);
      foreach ($result as $key) {
        $bundledProduct = DB::table('products as T1')->where('T1.id', '=', $key['bundled'])->where('T1.deleted_at' , '=', null)->get();
        $traceQty = app($this->productTraceController)->getProductQtyByParams($result[$i]['bundled'], $result[$i]['product_attribute_id']);
        $totalTransferredBundled = app($this->transferredProductController)->getTotalTransferredBundledProducts($result[$i]['bundled']);
        
        $parentTrace = app($this->productTraceController)->getTraceByParams(
            array(
              array('product_id', '=', $result[$i]['product_id']),
              array('product_attribute_id', '=', $result[$i]['product_attribute_id']),
            ),
            ['id', 'product_attribute_id', 'product_id', 'batch_number']
        );
        // $result[$i]['product'] = app($this->productController)->getByParamsWithReturn('id', $result[$i]['product_id'], ['title', 'id', 'tags']);
        $result[$i]['parent_trace'] = $parentTrace;
        $result[$i]['variation'] = app($this->productAttrController)->getByParamsWithMerchant('id', $result[$i]['product_attribute_id'], $merchantId);
        $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
        $result[$i]['qty'] = (int)$result[$i]['qty'];
        $result[$i]['scanned_qty'] =  $traceQty - sizeOf($totalTransferredBundled);
        $result[$i]['product'] = app($this->productController)->getProductColumnWithReturns('id', $result[$i]['bundled'], ['title']);
        $result[$i]['component_product'] = app($this->productController)->getProductColumnWithReturns('id', $result[$i]['product_id'], ['title']);
        $result[$i]['component_qty'] = app($this->productTraceController)->getProductQtyByParams($result[$i]['product_id'], $result[$i]['product_attribute_id']);
        $result[$i]['available_stock'] = app($this->productTraceController)->getProductQtyByParams($result[$i]['bundled'], $result[$i]['product_attribute_id']);
        if(sizeof($bundledProduct) > 0){
          array_push($res, $result[$i]);
        }
        $i++; 
      }
    }
    return sizeof($res) > 0 ? $res : [];
  }

  public function getByParamsDetails($column, $value){
    $result = BundledSetting::where($column, '=', $value)->where('deleted_at', '=', null)->get();
    return sizeof($result) > 0 ? $result : [];
  }

  public function getQtyByParams($productID, $AttrId){
    $result = BundledSetting::where('product_id', '=', $productID)->where('product_attribute_id', '=', $AttrId)->where('deleted_at', '=', null)->get();
    return sizeof($result) > 0 ? $result : [];
  }

  public function getQtyByParamsBundled($productID, $AttrId){
    $result = BundledSetting::where('bundled', '=', $productID)->where('product_attribute_id', '=', $AttrId)->where('deleted_at', '=', null)->get();
    return sizeof($result) > 0 ? $result : [];
  }

  public function getByParamsByCondition($condition){
    $result = BundledSetting::where($condition)->where('deleted_at', '=', null)->get();
    return sizeof($result) > 0 ? $result : [];
  }

  public function getByParamsByConditionWithDelete($condition){
    $result = BundledSetting::where($condition)->where('deleted_at', '=', null)->get();
    if(sizeof($result) > 0){
      BundledSetting::where($condition)->update(array('deleted_at' => Carbon::now()));
    }
    return sizeof($result) > 0 ? $result : [];
  }
  
  public function countNumberOfBundledPerProd($productId, $attrId){
    return BundledSetting::where('product_id', '=', $productId)->where('product_attribute_id', '=', $attrId)->count();
  }

  public function countNumberOfBundled($column, $value, $attrId){
    return BundledSetting::where($column, '=', $value)->where('product_attribute_id', '=', $attrId)->count();
  }


}
