<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Wishlist;

class WishlistController extends APIController
{
    function __construct(){
    	$this->model = new Wishlist();
    }

    public function retrieve(Request $request){
    	$data = $request->all();
    	$accountId = $data['account_id'];
    	$this->model = new Wishlist();
    	$this->retrieveDB($data);
    	$result = $this->response['data'];
    	if(sizeof($result) > 0){
    		$i = 0;
    		foreach ($result as $key) {
    			$payloadValue = $result[$i]['payload_value'];
    			if($result[$i]['payload'] == 'product'){
    				$this->response['data'][$i]['product'] = app('Increment\Marketplace\Http\ProductController')->retrieveProductById($payloadValue, $accountId);
    			}
    			$i++;
    		}
    		return $this->response();
    	}
    	return $this->response();
    }

    public function checkWishlist($id, $accountId){
      $result = Wishlist::where('payload_value', '=', $id)->where('payload', '=', 'product')->where('account_id', '=', $accountId)->get();
      return (sizeof($result) > 0) ? true : false;
    }
}
