<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Customer;
use Carbon\Carbon;
class CustomerController extends APIController
{

  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
  public $emailClass = 'App\Http\Controllers\EmailController';

  function __construct(){
    $this->model = new Customer();
    $this->notRequired = array(
      'merchant_id', 'email'
    );
  }

  public function create(Request $request){
    $data = $request->all();
    if(isset($data['business_code'])){
      $getMerchant = app($this->merchantClass)->getByParams('business_code', $data['business_code']);
      if($getMerchant != null){
        $this->model = new Customer();
        $code = $this->generateCode();
        $params = array(
          'code'        => $code,
          'merchant'    => $data['merchant'],
          'merchant_id' => $getMerchant['id'],
          'status'      => 'pending'
        );
        $this->insertDB($params);
        if($this->response['data'] > 0){
          $account = app('Increment\Account\Http\AccountController')->retrieveById($getMerchant['account_id']);
          $template = array(
            'subject' => 'NEW MERCHANT LINK REQUEST',
            'view'    => 'email.customerinvitation'
          );
          $data['email'] = $account[0]['email'];
          $data['code'] = $code;
          $data['username'] = $account[0]['username'];
          app($this->emailClass)->sendCustomerInvitation($data, $template);
        }
        return $this->response();
      }else{
        $this->response['data'] = null;
        $this->response['error'] = 'Business code was not found!';
        return $this->response();
      }
    }else{
      if(!isset($data['email'])){
        $this->response['data'] = null;
        $this->response['error'] = 'Email address is required!';
        return $this->response();
      }
      $this->model = new Customer();
      $code = $this->generateCode();
      $params = array(
        'code'        => $code,
        'merchant'    => $data['merchant'],
        'email'       => $data['email'],
        'status'      => 'pending'
      );
      $this->insertDB($params);
      if($this->response['data'] > 0){
        $template = array(
          'subject' => 'YOUR INVITATION TO AGRICORD',
          'view'    => 'email.noncustomerinvitation'
        );
        $data['email'] = $data['email']
        $data['code'] = $code;
        $data['username'] = $data['email']
        app($this->emailClass)->sendCustomerInvitation($data, $template);
      }
      return $this->response();
    }
  }

  public function generateCode(){
    $code = 'CUST-'.substr(str_shuffle($this->codeSource), 0, 59);
    $codeExist = Customer::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }
}
