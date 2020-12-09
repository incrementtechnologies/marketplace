<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\ProductInventory;
use Increment\Marketplace\Models\Product;
use DB;
use Carbon\Carbon;
class ProductInventoryController extends APIController
{
  public $productController = 'Increment\Marketplace\Http\ProductController';
  public $productAttrController = 'Increment\Marketplace\Http\ProductAttributeController';

    function __construct(){
      $this->model = new ProductInventory();
    }

    public function getInventory($productId){
      $this->localization();
      $result = ProductInventory::where('product_id', '=', $productId)->get();

      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
         $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i');
         $i++; 
        }
      }
      return (sizeof($result) > 0) ? $result : null;
    }

    public function getQty($id){
      return ProductInventory::where('product_id', '=', $id)->sum('qty');
    }

    public function getProductInvetory(Request $request){
        $data = $request->all();
        $tempRes = array();
        $result = DB::table('products')
                ->where('tags', 'like', '%'.$data['tags'].'%')
                ->where('merchant_id', '=', $data['merchant_id'])
                ->select('*')
                ->get();
        if(sizeof($result) > 0){
          $i = 0;
          foreach ($result as  $value) {
            $tempRes[$i]['name'] = $result[$i]->title;
            $attribute = app($this->productAttrController)->getByParams('product_id', $result[$i]->id);
            // $tempRes[$i]['inventories'] = $this->getInventory($result[$i]->id);
            $attributes = array(
              'product_id' => $attribute[0]['product_id'],
              'payload' => $attribute[0]['payload'],
              'payload_value' => $attribute[0]['payload_value']
            );
            $tempRes[$i]['variation'] = $attributes;
            $tempRes[$i]['qty'] = app($this->productController)->getRemainingQty($result[$i]->id);
            $i++;
          }
        }
        $this->response['data'] = $tempRes;
        return $this->response();
    }
}
