<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\CheckoutItem;
use Increment\Marketplace\Models\Checkout;
use App\CheckoutTemplate;
use Carbon\Carbon;
class CheckoutItemController extends APIController
{
    public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
    function __construct(){
    	$this->model = new CheckoutItem();

        $this->notRequired = array(
            'color', 'size'
        );
    }

    public function create(Request $request){
        $data = $request->all();
        $insertData = array(
            'account_id' => $data['account_id'],
            'payload' => $data['payload'],
            'payload_value' => $data['payload_value'],
            'price' => $data['price'],
            'qty' => $data['qty'],
            'size' => isset($data['size']) ? $data['size'] : null,
            'color' => isset($data['color']) ? $data['color'] : null,
            'status' => 'printing'
        );
    	$accountId = $data['account_id'];
    	$checkout = Checkout::where('account_id', '=', $accountId)->where('status', '=', 'added')->first();
    	if($checkout){
    		$insertData['checkout_id'] = $checkout->id;
    		$this->model = new CheckoutItem();
    		$this->insertDB($insertData);
    		return $this->response();
    	}else{
    		$checkout = new Checkout();
        $checkout->payload = $data['type'];
    		$checkout->account_id = $data['account_id'];
        $checkout->coupon_id = null;
        $checkout->order_number = app($this->merchantClass)->getOrderNumber($data['account_id']);
    		$checkout->sub_total = 0;
    		$checkout->tax = 0;
    		$checkout->total = 0;
    		$checkout->status = 'added';
        $checkout->payment_status = 'added';
    		$checkout->save();
    		if($checkout->id){
          if($data['payload'] == 'profile'){
            // save checkout template for profiles checkout
            $checkoutTemplate = new CheckoutTemplate();
            $checkoutTemplate->checkout_id = $checkout->id;
            $checkoutTemplate->front = $data['front'];
            $checkoutTemplate->back = $data['back'];
            $checkoutTemplate->created_at = Carbon::now();
            $checkoutTemplate->save();
          }
          
    			$insertData['checkout_id'] = $checkout->id;
	    		$this->model = new CheckoutItem();
	    		$this->insertDB($insertData);
	    		return $this->response();
    		}else{
		    	return response()->json(array(
		    		'data'	=> null,
		    		'error' => null,
		    		'timestamps' => Carbon::now()
		    	));
    		}
    	}
    }

    public function getQty($payload, $payloadValue){
        return CheckoutItem::where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->where('status', '!=', 'cancelled')->sum('qty');
    }
}
