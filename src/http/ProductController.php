<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Product;
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
    function __construct(){
    	$this->model = new Product();
      $this->notRequired = array(
        'tags', 'sku'
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
      $codeExist = Product::where('id', '=', $code)->get();
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
      $result = $this->response['data'];
      // details
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $this->response['data'][$i]['price'] = app($this->productPricingController)->getPrice($result[$i]['id']);
          $this->response['data'][$i]['color'] = app($this->productAttrController)->getAttribute($result[$i]['id'], 'color');
          $this->response['data'][$i]['size'] = app($this->productAttrController)->getAttribute($result[$i]['id'], 'size');
          $this->response['data'][$i]['featured'] = app($this->productImageController)->getProductImage($result[$i]['id'], 'featured');
          $this->response['data'][$i]['images'] = app($this->productImageController)->getProductImage($result[$i]['id'], null);
          $this->response['data'][$i]['tag_array'] = $this->manageTags($result[$i]['tags']);
          $this->response['data'][$i]['wishlist_flag'] = app($this->wishlistController)->checkWishlist($result[$i]['id'], $accountId);
          $this->response['data'][$i]['checkout_flag'] = app($this->checkoutController)->checkCheckout($result[$i]['id'], $accountId);
          $this->response['data'][$i]['inventories'] = null;
          $this->response['data'][$i]['product_traces'] = null;
          if($inventoryType == 'inventory'){
            $this->response['data'][$i]['inventories'] = app($this->inventoryController)->getInventory($result[$i]['id']);
            $this->response['data'][$i]['qty'] = $this->getRemainingQty($result[$i]['id']);
          }else if($inventoryType == 'product_trace'){
            $this->response['data'][$i]['product_traces'] =  app($this->productTraceController)->getByParams('product_id', $result[$i]['id']);
            $this->response['data'][$i]['qty'] = app($this->productTraceController)->getBalanceQty('product_id', $result[$i]['id']);
          }
          $i++;
        }
      }
      return $this->response();
    }

    public function getRemainingQty($id){
      $issued = intval(app($this->checkoutItemController)->getQty('product', $id));
      $total = intval(app($this->inventoryController)->getQty($id));
      return $total - $issued;
    }

    public function retrieveProductById($id, $accountId){
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
      $result = $this->response['data'];
      // details
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
          $result[$i]['inventories'] = app($this->inventoryController)->getInventory($result[$i]['id']);
          $result[$i]['qty'] = $this->getRemainingQty($result[$i]['id']);
          if($accountId != null){
            $result[$i]['wishlist_flag'] = app($this->wishlistController)->checkWishlist($result[$i]['id'], $accountId);
            $result[$i]['checkout_flag'] = app($this->checkoutController)->checkCheckout($result[$i]['id'], $accountId);
          }
          $i++;
        }
      }
      return (sizeof($result) > 0) ? $result[0] : null;
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
