<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Product;
use Carbon\Carbon;
class ProductController extends APIController
{
    public $productImageController = 'Increment\Marketplace\Http\ProductImageController';
    public $productAttrController = 'Increment\Marketplace\Http\ProductAttributeController';
    public $productPricingController = 'Increment\Marketplace\Http\PricingController';
    public $wishlistController = 'Increment\Marketplace\Http\WishlistController';
    public $checkoutController = 'Increment\Marketplace\Http\CheckoutController';
    public $checkoutItemController = 'Increment\Marketplace\Http\CheckoutItemController';
    public $inventoryController = 'Increment\Marketplace\Http\ProductInventoryController';
    public $productTraceController = 'Increment\Marketplace\Http\ProductTraceController';
    public $merchantController = 'Increment\Marketplace\Http\MerchantController';
    public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
    public $bundledSettingController = 'Increment\Marketplace\Http\BundledSettingController';
    function __construct(){
    	$this->model = new Product();
      $this->notRequired = array(
        'tags', 'sku', 'rf'
      );
    }

    public function create(Request $request){
    	$data = $request->all();
    	$data['code'] = $this->generateCode();
      $data['price_settings'] = 'fixed';
    	$this->model = new Product();
    	$this->insertDB($data);
    	return $this->response();
    }


    public function generateCode(){
      $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32);
      $codeExist = Product::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $inventoryType = $data['inventory_type'];
      $accountId = $data['account_id'];
      $this->model = new Product();
      $this->retrieveDB($data);
      $this->response['data'] = $this->manageResult($this->response['data'], null, $inventoryType);
      return $this->response();
    }

    public function getRemainingQty($id){
      $issued = intval(app($this->checkoutItemController)->getQty('product', $id));
      $total = intval(app($this->inventoryController)->getQty($id));
      return $total - $issued;
    }

    public function retrieveProductById($id, $accountId, $inventoryType = null){
      $inventoryType = $inventoryType == null ? env('INVENTORY_TYPE') : $inventoryType;
      //on wishlist, add parameter inventory type
      //on checkout, add parameter inventory type
      $data = array(
        'condition' => array(array(
          'value'   => $id,
          'column'  => 'id',
          'clause'  => '='
        ))
      );

      $this->model = new Product();
      $this->retrieveDB($data);
      $result = $this->manageResult($this->response['data'], $accountId, $inventoryType);
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function getByParams($column, $value){
      $result = Product::where($column, '=', $value)->get();
      return sizeof($result) > 0 ? $result[0] : null;
    }

    public function getProductByParams($column, $value){
      $result = Product::where($column, '=', $value)->get();
      if(sizeof($result) > 0){
        $i= 0;
        foreach ($result as $key) {
           $result[$i]['merchant'] = app($this->merchantController)->getByParams('id', $result[$i]['merchant_id']);
         } 
      }
      return sizeof($result) > 0 ? $result[0] : null;      
    }

    public function manageResult($result, $accountId, $inventoryType){
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $result[$i]['price'] = app($this->productPricingController)->getPrice($result[$i]['id']);
          $result[$i]['color'] = app($this->productAttrController)->getAttribute($result[$i]['id'], 'color');
          $result[$i]['size'] = app($this->productAttrController)->getAttribute($result[$i]['id'], 'size');
          $result[$i]['featured'] = app($this->productImageController)->getProductImage($result[$i]['id'], 'featured');
          $result[$i]['images'] = app($this->productImageController)->getProductImage($result[$i]['id'], null);
          $result[$i]['tag_array'] = $this->manageTags($result[$i]['tags']);
          $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y H:i A');
          $result[$i]['bundled_products'] = app($this->bundledProductController)->getByParams('product_id', $result[$i]['id']);
          $result[$i]['bundled_settings'] = app($this->bundledSettingController)->getByParams('bundled', $result[$i]['id']);
          if($accountId !== null){
            $result[$i]['wishlist_flag'] = app($this->wishlistController)->checkWishlist($result[$i]['id'], $accountId);
            $result[$i]['checkout_flag'] = app($this->checkoutController)->checkCheckout($result[$i]['id'], $accountId); 
          }
          $result[$i]['inventories'] = null;
          $result[$i]['product_traces'] = null;
          $result[$i]['merchant'] = app($this->merchantController)->getByParams('id', $result[$i]['merchant_id']);
          if($inventoryType == 'inventory'){
            $result[$i]['inventories'] = app($this->inventoryController)->getInventory($result[$i]['id']);
            $result[$i]['qty'] = $this->getRemainingQty($result[$i]['id']);
          }else if($inventoryType == 'product_trace'){
            $result[$i]['product_traces'] =  app($this->productTraceController)->getByParams('product_id', $result[$i]['id']);
            $result[$i]['qty'] = app($this->productTraceController)->getBalanceQty('product_id', $result[$i]['id']);
          }
          $i++;
        }
      }
      return $result;
    }

    public function manageTags($tags){
      $result = array();
      if($tags != null){
        if(strpos($tags, ',')){
            $array  = explode(',', $tags);
            if(sizeof($array) > 0){
              for ($i = 0; $i < sizeof($array); $i++) { 
                $resultArray = array(
                  'title' => $array[$i]
                );
                $result[] = $resultArray;
              }
              return $result;
            }else{
              return null;
            }
        }else{
        }
      }else{
        return null;
      }
    }

}
