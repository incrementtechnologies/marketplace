<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Product;
use Illuminate\Support\Facades\Storage;
use Increment\Common\Image\Models\Image;
use Increment\Common\Payload\Models\Payload;
use Illuminate\Support\Facades\DB;
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
    public $transferClasss = 'Increment\Marketplace\Http\TransferController';
    function __construct(){
    	$this->model = new Product();
      $this->notRequired = array(
        'tags', 'sku', 'rf'
      );
      $this->localization();
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
      $code = 'PR_'.substr(str_shuffle($this->codeSource), 0, 61);
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

    public function retrieveMobile(Request $request){
      $data = $request->all();
      $inventoryType = $data['inventory_type'];
      $accountId = $data['account_id'];
      $this->model = new Product();
      $this->retrieveDB($data);
      $this->response['data'] = $this->manageResultMobile($this->response['data'], null, $inventoryType);
      return $this->response();
    }

     public function retrieveBasic(Request $request){
      $data = $request->all();
      $inventoryType = $data['inventory_type'];
      $accountId = $data['account_id'];
      $this->model = new Product();
      $this->retrieveDB($data);
      $this->response['data'] = $this->manageResultBasic($this->response['data'], null, $inventoryType);

      if(sizeof($data['condition']) == 3){
        $this->response['size'] = Product::where($data['condition'][0]['column'], $data['condition'][0]['clause'], $data['condition'][0]['value'])
        ->where($data['condition'][1]['column'], $data['condition'][1]['clause'], $data['condition'][1]['value'])
        ->where($data['condition'][2]['column'], $data['condition'][2]['clause'], $data['condition'][2]['value'])->count();
      }else if(sizeof($data['condition']) == 2){
        $this->response['size'] = Product::where($data['condition'][0]['column'], $data['condition'][0]['clause'], $data['condition'][0]['value'])->where($data['condition'][1]['column'], $data['condition'][1]['clause'], $data['condition'][1]['value'])->count();
      }else if(sizeof($data['condition']) == 1){
        $this->response['size'] = Product::where($data['condition'][0]['column'], $data['condition'][0]['clause'], $data['condition'][0]['value'])->count();
      }
      
      return $this->response();
    }

     public function retrieveWithOrderNumber(Request $request){
      $data = $request->all();
      $inventoryType = $data['inventory_type'];
      $accountId = $data['account_id'];
      $this->model = new Product();
      $this->retrieveDB($data);
      $this->response['data'] = $this->manageResultBasic($this->response['data'], null, $inventoryType);
      $result = $this->response['data'];

      if(sizeof($result) > 0 && $data['order_request_id'] != null){
        $this->response['data'][0]['product_trace'] = app($this->transferClasss)->getProductTraceByOrderId($data['order_request_id'], $result[0]['id']);
      }
      
      return $this->response();
    }

    public function getRemainingQty($id){
      $issued = intval(app($this->checkoutItemController)->getQty('product', $id));
      $total = intval(app($this->inventoryController)->getQty($id));
      return $total - $issued;
    }

    public function fileUpload(Request $request){
      $data = $request->all();
      if(isset($data['file_url'])){
        $date = Carbon::now()->toDateString();
        $time = str_replace(':', '_',Carbon::now()->toTimeString());
        $ext = $request->file('file')->extension();
        // $fileUrl = str_replace(' ', '_', $data['file_url']);
        // $fileUrl = str_replace('%20', '_', $fileUrl);
        $filename = $data['account_id'].'_'.$data['file_url'];
        $result = $request->file('file')->storeAs('files', $filename);
        $url = '/storage/files/'.$filename;
        $this->model = new Image();
        $insertData = array(
          'account_id'    => $data['account_id'],
          'url'           => $url
        );
        $this->insertDB($insertData);
        // $this->response['data'] = $url;
        $payload_id = $this->response['data'];
        $this->model = new Payload();
        $payloadData = array(
          'account_id' => $data['account_id'],
          'payload' => 'product'.$data['product_id'],
          'category' => $data['product_code'],
          'payload_value' => array(
            'url' => $url,
            'filename' => $filename
          )
        );
        $this->insertDB($payloadData);
        return $this->response();
      }
      return response()->json(array(
        'data'  => null,
        'error' => null,
        'timestamps' => Carbon::now()
      ));
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

    public function getProductColumnByParams($column, $value, $productColumn){
      $result = Product::where($column, '=', $value)->get();
      return sizeof($result) > 0 ? $result[0][$productColumn] : null;
    }

    public function getProductTitleWithTags($column, $value){
      $result = Product::where($column, '=', $value)->select('title', 'tags', 'merchant_id', 'description')->get();
      return sizeof($result) > 0 ? $result : null;
    }

    public function getProductByParams($column, $value){
      $result = Product::where($column, '=', $value)->get();
      if(sizeof($result) > 0){
        $i= 0;
        foreach ($result as $key) {
          $result[$i]['merchant'] = app($this->merchantController)->getByParams('id', $result[$i]['merchant_id']);
          $result[$i]['featured'] = app($this->productImageController)->getProductImage($result[$i]['id'], 'featured');
          // $result[$i]['images'] = app($this->productImageController)->getProductImage($result[$i]['id'], null);
          // $result[$i]['variation'] = app($this->productAttrController)->getByParams('product_id', $result[$i]['id']);
         } 
      }
      return sizeof($result) > 0 ? $result[0] : null;      
    }


    public function getProductByParamsConsignments($column, $value){
      $result = Product::where($column, '=', $value)->get(['id', 'code', 'type', 'title', 'merchant_id']);
      if(sizeof($result) > 0){
        $i= 0;
        foreach ($result as $key) {
          $result[$i]['merchant'] = app($this->merchantController)->getByParamsConsignments('id', $result[$i]['merchant_id']);
          $result[$i]['featured'] = app($this->productImageController)->getProductImage($result[$i]['id'], 'featured');
          // $result[$i]['images'] = app($this->productImageController)->getProductImage($result[$i]['id'], null);
          $result[$i]['variation'] = app($this->productAttrController)->getByParams('product_id', $result[$i]['id']);
         } 
      }
      return sizeof($result) > 0 ? $result[0] : null;      
    }


    public function getProductByParamsOrderDetails($column, $value){
      $result = Product::where($column, '=', $value)->get(['id', 'code', 'type', 'title', 'merchant_id']);
      if(sizeof($result) > 0){
        $i= 0;
        foreach ($result as $key) {
          $result[$i]['merchant'] = app($this->merchantController)->getByParamsProduct('id', $result[$i]['merchant_id']);
          $result[$i]['variation'] = app($this->productAttrController)->getByParams('product_id', $result[$i]['id']);
          $result[$i]['qty'] = app($this->transferClasss)->getQtyTransferred($result[$i]['merchant_id'], $result[$i]['id']);
         } 
      }
      return sizeof($result) > 0 ? $result : null;      
    }

    public function getProductName($column, $value){
      $result = Product::where($column, '=', $value)->get(['id', 'code', 'type', 'title', 'merchant_id']);
      if(sizeof($result) > 0){
        $i= 0;
        foreach ($result as $key) {
          $result[$i]['variation'] = app($this->productAttrController)->getByParams('product_id', $result[$i]['id']);
          $result[$i]['qty'] = app($this->transferClasss)->getQtyTransferred($result[$i]['merchant_id'], $result[$i]['id']);
         } 
      }
      return sizeof($result) > 0 ? $result : null;      
    }


    public function getProductByVariations($column, $value){
      $result = Product::where($column, '=', $value)->get(['id', 'code', 'type', 'title', 'merchant_id']);
      if(sizeof($result) > 0){
        $i= 0;
        foreach ($result as $key) {
          $result[$i]['variation'] = app($this->productAttrController)->getByParams('product_id', $result[$i]['id']);
         } 
      }
      return sizeof($result) > 0 ? $result[0] : null;      
    }

    
    public function manageResultBasic($result, $accountId, $inventoryType){
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['featured'] = app($this->productImageController)->getProductImage($result[$i]['id'], 'featured');
          $result[$i]['tag_array'] = $this->manageTags($result[$i]['tags']);
          $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
          $result[$i]['inventories'] = null;
          $result[$i]['product_traces'] = null;
          $result[$i]['merchant'] = app($this->merchantController)->getByParams('id', $result[$i]['merchant_id']);
          if($inventoryType == 'inventory'){
            $result[$i]['inventories'] = app($this->inventoryController)->getInventory($result[$i]['id']);
            $result[$i]['qty'] = $this->getRemainingQty($result[$i]['id']);
          }else if($inventoryType == 'product_trace'){
            // $result[$i]['product_traces'] =  app($this->productTraceController)->getByParams('product_id', $result[$i]['id']);
            $qty = app($this->productTraceController)->getBalanceQtyOnManufacturer('product_id', $result[$i]['id']);
            $result[$i]['qty'] = $qty['qty'];
            $result[$i]['qty_in_bundled'] = $qty['qty_in_bundled'];
          }
          $i++;
        }
      }
      return $result;
    }

    public function manageResult($result, $accountId, $inventoryType){
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          // $result[$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          // $result[$i]['price'] = app($this->productPricingController)->getPrice($result[$i]['id']);
          $result[$i]['variation'] = app($this->productAttrController)->getByParams('product_id', $result[$i]['id']);
          $result[$i]['featured'] = app($this->productImageController)->getProductImage($result[$i]['id'], 'featured');
          $result[$i]['images'] = app($this->productImageController)->getProductImage($result[$i]['id'], null);
          $result[$i]['tag_array'] = $this->manageTags($result[$i]['tags']);
          $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
          // $result[$i]['bundled_products'] = app($this->bundledProductController)->getByParams('product_id', $result[$i]['id']);
          // $result[$i]['bundled_settings'] = app($this->bundledSettingController)->getByParams('bundled', $result[$i]['id']);
          // if($accountId !== null){
          //   $result[$i]['wishlist_flag'] = app($this->wishlistController)->checkWishlist($result[$i]['id'], $accountId);
          //   $result[$i]['checkout_flag'] = app($this->checkoutController)->checkCheckout($result[$i]['id'], $accountId); 
          // }
          $result[$i]['inventories'] = null;
          $result[$i]['product_traces'] = null;
          $result[$i]['merchant'] = app($this->merchantController)->getByParams('id', $result[$i]['merchant_id']);
          if($inventoryType == 'inventory'){
            $result[$i]['inventories'] = app($this->inventoryController)->getInventory($result[$i]['id']);
            $result[$i]['qty'] = $this->getRemainingQty($result[$i]['id']);
          }else if($inventoryType == 'product_trace'){
            // $result[$i]['product_traces'] =  app($this->productTraceController)->getByParams('product_id', $result[$i]['id']);
            $qty = app($this->productTraceController)->getBalanceQtyOnManufacturer('product_id', $result[$i]['id']);
            $result[$i]['qty'] = $qty['qty'];
            $result[$i]['qty_in_bundled'] = $qty['qty_in_bundled'];
          }
          $i++;
        }
      }
      return $result;
    }

    public function manageResultMobile($result, $accountId, $inventoryType){
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          // $result[$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          // $result[$i]['price'] = app($this->productPricingController)->getPrice($result[$i]['id']);
          $result[$i]['variation'] = app($this->productAttrController)->getByParams('product_id', $result[$i]['id']);
          $result[$i]['featured'] = app($this->productImageController)->getProductImage($result[$i]['id'], 'featured');
          $result[$i]['images'] = app($this->productImageController)->getProductImage($result[$i]['id'], null);
          $result[$i]['tag_array'] = $this->manageTags($result[$i]['tags']);
          $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
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
            // $result[$i]['product_traces'] =  app($this->productTraceController)->getByParams('product_id', $result[$i]['id']);
            $qty = app($this->productTraceController)->getBalanceQtyWithInBundled('product_id', $result[$i]['id']);
            $result[$i]['qty'] = $qty['qty'];
            $result[$i]['qty_in_bundled'] = $qty['qty_in_bundled'];
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
