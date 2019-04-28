<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\ShippingAddress;
class ShippingAddressController extends APIController
{

  public $billingInformationClass = 'Increment\Account\Http\BillingInformationController';
  function __construct(){
    $this->model = new ShippingAddress();

    $this->notRequired = array(
    	'payload', 'payload_value', 'contact_number', 'notes'
    );
  }


  public function getShippingAddress($checkoutId){
    $result = ShippingAddress::where('checkout_id', '=', $checkoutId)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        if($result[$i]['payload'] == 'billing'){
          $result[$i]['payload_details'] = app($this->billingInformationClass)->getBillingInformation($result[$i]['payload_value']);
        }
        $i++;
      }
    }
    return (sizeof($result) > 0) ? $result[0] : null;
  }

}
