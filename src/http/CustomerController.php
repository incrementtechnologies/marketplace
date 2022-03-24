<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Str;
use DB;
class CustomerController extends APIController
{

  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
  public $accountClass = 'Increment\Account\Http\AccountController';
  public $emailClass = 'App\Http\Controllers\EmailController';

  function __construct(){
    $this->model = new Customer();
    $this->notRequired = array(
      'merchant_id', 'email'
    );
  }


  public function manageMerchant($data, $column, $value, $flag){
    $merchant = app($this->merchantClass)->getByParams($column, $value);
    if($merchant != null){
      if($this->checkIfExist($data['merchant'], 'merchant_id', $merchant['id']) == true){
        if($flag == true){
          return;
        }
        $this->response['data'] = null;
        $this->response['error'] = 'Merchant already existed to the list.';
        return $this->response();
      }
      $this->model = new Customer();
      $code = $this->generateCode();
      $params = array(
        'code'        => $code,
        'merchant'    => $data['merchant'],
        'merchant_id' => $merchant['id'],
        'status'      => 'pending'
      );
      $this->insertDB($params);
      if($this->response['data'] > 0){
          $account = app('Increment\Account\Http\AccountController')->retrieveById($merchant['account_id']);
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
      if($flag == true){
        return;
      }
      $this->response['data'] = null;
      $this->response['error'] = 'Business code was not found!';
      return $this->response();
    }
  }
  

  public function create(Request $request){
    $data = $request->all();
    if(isset($data['business_code'])){
      if($this->selfInvitation($data['merchant'], null, $data['business_code']) == true){
        $this->response['data'] = null;
        $this->response['error'] = 'You cannot invite yourself';
        return $this->response();
      }else{
        return $this->manageMerchant($data, 'business_code', $data['business_code'], false);
      }
    }else{
      if(!isset($data['email'])){
        $this->response['data'] = null;
        $this->response['error'] = 'Email address is required!';
        return $this->response();
      }
      if($this->selfInvitation($data['merchant'], $data['email'], null) == true){
        $this->response['data'] = null;
        $this->response['error'] = 'You cannot invite yourself';
        return $this->response();
      }
      if($this->checkIfExist($data['merchant'], 'email', $data['email']) == true){
        $this->response['data'] = null;
        $this->response['error'] = 'Email already existed to the list.';
        return $this->response();
      }
      
      $account = app('Increment\Account\Http\AccountController')->retrieveByEmail($data['email']);
      if($account != null){
        $this->manageMerchant($data, 'account_id', $account['id'], true);
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
        $data['email'] = $data['email'];
        $data['code'] = $code;
        $data['username'] = $data['email'];
        app($this->emailClass)->sendCustomerInvitation($data, $template);
      }
      return $this->response();
    }
  }

  public function resend(Request $request){
    $data = $request->all();
    if($data['merchant_id'] != null){
      $getMerchant = app($this->merchantClass)->getByParams('id', $data['merchant_id']);
      if($getMerchant != null){
        $account = app('Increment\Account\Http\AccountController')->retrieveById($getMerchant['account_id']);
        $template = array(
          'subject' => 'NEW MERCHANT LINK REQUEST',
          'view'    => 'email.customerinvitation'
        );
        $data['email'] = $account[0]['email'];
        $data['username'] = $account[0]['username'];
        app($this->emailClass)->sendCustomerInvitation($data, $template);
      }
    }else{
      $template = array(
        'subject' => 'YOUR INVITATION TO AGRICORD',
        'view'    => 'email.noncustomerinvitation'
      );
      $data['username'] = $data['email'];
      app($this->emailClass)->sendCustomerInvitation($data, $template);
    }
    return $this->response();
  }

  public function update(Request $request){
    $data = $request->all();
    $this->updateDB($data);
    if($this->response['data']){

      if($data['status'] == 'approved'){
        // Send to receiver
        $getMerchant = app($this->merchantClass)->getByParams('id', $data['merchant_id']);
        $account = app('Increment\Account\Http\AccountController')->retrieveById($getMerchant['account_id']);
        $template = array(
          'subject' => 'BUSINESS LINK AGE SUCCESSFUL',
          'view'    => 'email.customerconfirmationreceiver'
        );
        $data['email'] = $account[0]['email'];
        $data['username'] = $account[0]['username'];
        $data['receiver_merchant_id'] = $data['merchant'];
        app($this->emailClass)->sendCustomerInvitation($data, $template);


        // Send to sender
        $getMerchant = app($this->merchantClass)->getByParams('id', $data['merchant']);
        $account = app('Increment\Account\Http\AccountController')->retrieveById($getMerchant['account_id']);
        $template = array(
          'subject' => 'BUSINESS LINK AGE SUCCESSFUL',
          'view'    => 'email.customerconfirmationsender'
        );
        $data['email'] = $account[0]['email'];
        $data['username'] = $account[0]['username'];
        $data['receiver_merchant_id'] = $data['merchant_id'];
        app($this->emailClass)->sendCustomerConfirmation($data, $template);
      }
    }
    return $this->response();
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['merchant_details'] = null;
        if($result[$i]['merchant_id'] != null){
          $this->response['data'][$i]['merchant_details'] = app($this->merchantClass)->getByParamsWithAccount('id', $result[$i]['merchant_id']);
        }
        $this->response['data'][$i]['merchant_sender_details'] = app($this->merchantClass)->getByParamsWithAccount('id', $result[$i]['merchant']);
        $i++;
      }
    }
    return $this->response();
  }

  public function retrieveAllowedOnly(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    
    if(sizeof($result) > 0){
      $i = 0;
      $array = array();
      foreach ($result as $key) {
        $name = null;
        $merchant = null;
       
        if($data['merchant_id'] == $key['merchant']){
          $merchant = app($this->merchantClass)->getByParamsWithAccount('id', $key['merchant_id']);
        }else{
          $merchant = app($this->merchantClass)->getByParamsWithAccount('id', $key['merchant']);
        }

        if($key['merchant_id'] == null){
          $name = $key['email'];
        }else{
          $name = $merchant ? $merchant['name'] : null;
        }

        $type = $merchant ? $merchant['account'][0]['account_type'] : null;
              
        $item = array(
          'name'    => $name,
          'type'    => $type,
          'status'  => $key['status'],
          'merchant'  => $key['merchant'],
          'merchant_id'  => $key['merchant_id'],
          'code'  => $key['code'],
          'id'      => $key['id']
        );
        $array[] = $item;
      }
      $this->response['data'] = $array;
    }

    if(sizeof($data['condition']) == 1){
      $this->response['size'] = Customer::where($data['condition'][0]['column'], '=', $data['condition'][0]['value'])->orWhere()->count();
    }else if(sizeof($data['condition']) == 2){
      $this->response['size'] = Customer::where($data['condition'][0]['column'], '=', $data['condition'][0]['value'])->orWhere($data['condition'][1]['column'], '=', $data['condition'][1]['value'])->count();
    }

    return $this->response();
  }

  public $con = null;
  public function retrieveAll(Request $request) {
    $data = $request->all();
    $this->con = $data['condition'];
    $results = array();
    $name = null;
    $size = null;
    if($this->con[0]['value'] != '%%') {
      $name = DB::table('customers as T1')
        ->leftJoin('merchants as T2', 'T2.id', '=', 'T1.merchant_id')
        ->leftJoin('accounts as T3', 'T3.id', '=', 'T2.account_id')
        ->whereNull('T1.deleted_at')
        ->Where(function($quer){
              $quer->Where($this->con[1]['column'], $this->con[1]['clause'], $this->con[1]['value'])
              ->Where(function($query) {
                if($this->con[0]['column'] == 'email') {
                  return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value'])
                              ->orWhere('T2.name', $this->con[0]['clause'], $this->con[0]['value']);
                } else if ($this->con[0]['column'] == 'account_type') {
                  return $query->Where('T3.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
                } else {
                  return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
                }
              })
              ->orWhere($this->con[2]['column'], '=', $this->con[2]['value']);
        })
        ->select('T1.merchant', 'T1.merchant_id', 'T2.name', 'T3.account_type', 'T1.email', 'T1.code', 'T1.status', 'T1.id', 'T1.deleted_at')
        ->skip($data['offset'])
        ->take($data['limit'])
        ->orderBy(array_keys($data['sort'])[0], $data['sort'][$this->con[0]['column']])
        // ->orderBy('name', $data['sort'][$this->con[0]['column']])
        ->get();
        
        $size = DB::table('customers as T1')
        ->leftJoin('merchants as T2', 'T2.id', '=', 'T1.merchant_id')
        ->leftJoin('accounts as T3', 'T3.id', '=', 'T2.account_id')
        ->whereNull('T1.deleted_at')
        ->Where(function($quer){
              $quer->Where($this->con[1]['column'], $this->con[1]['clause'], $this->con[1]['value'])
              ->Where(function($query) {
                if($this->con[0]['column'] == 'email') {
                  return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value'])
                              ->orWhere('T2.name', $this->con[0]['clause'], $this->con[0]['value']);
                } else if ($this->con[0]['column'] == 'account_type') {
                  return $query->Where('T3.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
                } else {
                  return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
                }
              })
              ->orWhere($this->con[2]['column'], '=', $this->con[2]['value']);
        })
        ->select('T1.merchant', 'T1.merchant_id', 'T2.name', 'T3.account_type', 'T1.email', 'T1.code', 'T1.status', 'T1.id', 'T1.deleted_at')
        ->orderBy(array_keys($data['sort'])[0], $data['sort'][$this->con[0]['column']])
        // ->orderBy('name', $data['sort'][$this->con[0]['column']])
        ->get();
        $this->response['size'] = sizeof($size);
        // DB::table('customers as T1')
        //   ->leftJoin('merchants as T2', 'T1.merchant_id', '=', 'T2.id')
        //   ->leftJoin('accounts as T3', 'T2.account_id', '=', 'T3.id')
        //   ->Where($this->con[1]['column'], $this->con[1]['clause'], $this->con[1]['value'])
        //   ->Where(function($query) {
        //     if($this->con[0]['column'] == 'email') {
        //       return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value'])
        //                   ->orWhere('T2.name', $this->con[0]['clause'], $this->con[0]['value']);
        //     } else if ($this->con[0]['column'] == 'account_type') {
        //       return $query->Where('T3.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
        //     } else {
        //       return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
        //     }
        //   })
        //   ->whereNull('T1.deleted_at')
        //   ->orWhere($this->con[2]['column'], '=', $this->con[2]['value'])
        //   ->count();
    } else {
      $name = DB::table('customers')
        ->leftJoin('merchants', 'merchants.id', '=', 'customers.merchant_id')
        ->leftJoin('accounts', 'accounts.id', '=', 'merchants.account_id')
        ->whereNull('customers.deleted_at')
        ->Where(function($query) {
            $query->Where($this->con[1]['column'], $this->con[1]['clause'], $this->con[1]['value'])
                  ->orWhere($this->con[2]['column'], '=', $this->con[2]['value']);
        })
        ->select('customers.merchant', 'customers.merchant_id', 'merchants.name', 'accounts.account_type', 'customers.email', 'customers.code', 'customers.status', 'customers.id', 'customers.deleted_at')
        ->skip($data['offset'])
        ->take($data['limit'])
        ->orderBy($this->con[0]['column'], $data['sort'][$this->con[0]['column']])
        ->orderBy('name', $data['sort'][$this->con[0]['column']])
        ->get();

        $size =  DB::table('customers')
        ->leftJoin('merchants', 'merchants.id', '=', 'customers.merchant_id')
        ->leftJoin('accounts', 'accounts.id', '=', 'merchants.account_id')
        ->whereNull('customers.deleted_at')
        ->Where(function($query) {
            $query->Where($this->con[1]['column'], $this->con[1]['clause'], $this->con[1]['value'])
                  ->orWhere($this->con[2]['column'], '=', $this->con[2]['value']);
        })
        ->select('customers.merchant', 'customers.merchant_id', 'merchants.name', 'accounts.account_type', 'customers.email', 'customers.code', 'customers.status', 'customers.id', 'customers.deleted_at')
        ->orderBy($this->con[0]['column'], $data['sort'][$this->con[0]['column']])
        ->orderBy('name', $data['sort'][$this->con[0]['column']])
        ->get();
        $this->response['size'] = sizeof($size);

    }
    $i = 0;
    foreach($name as $element) {
      $name = null;
      $merchant = null;
      $type = null;
      if($element->email != null && $element->merchant_id == null){
        $name = $element->email;
        $accounts = app($this->accountClass)->retrieveByEmail($name);
        $type = $accounts['account_type'];
     }else{
        if(intVal($element->merchant) != intVal($data['merchant_id'])){
          $merchant = app($this->merchantClass)->getByParamsWithAccount('id', $element->merchant);
          $accounts = app($this->accountClass)->retrieveById($merchant->account_id);
          $type = $accounts[0]['account_type'];
          $name = $merchant['name'];
        }
         else {
          $name = $element->name;
          $type = $element->account_type;
        }
      }
      $column = $this->con[0]['column'];
      if($this->con[0]['value'] != '%%') {
        if($column == 'email') {
          if(Str::contains(Str::lower($name), Str::lower(explode('%', $this->con[0]['value'])[1]))) {
            $results[$i]['name'] = $name;
            $results[$i]['code'] = $element->code;
            $results[$i]['status'] = $element->status;
            $results[$i]['type'] = $type;
            $results[$i]['merchant'] = $element->merchant;
            $results[$i]['merchant_id'] = $element->merchant_id;
            $results[$i]['id'] = $element->id;
            $i++;
          }
        } else {
          if(Str::contains(Str::lower($element->$column), Str::lower(explode('%', $this->con[0]['value'])[1]))) {
            $results[$i]['name'] = $name;
            $results[$i]['code'] = $element->code;
            $results[$i]['status'] = $element->status;
            $results[$i]['type'] = $type;
            $results[$i]['merchant'] = $element->merchant;
            $results[$i]['merchant_id'] = $element->merchant_id;
            $results[$i]['id'] = $element->id;
            $i++;
          }
        }
      } else {
      $results[$i]['name'] = $name;
      $results[$i]['code'] = $element->code;
      $results[$i]['status'] = $element->status;
      $results[$i]['type'] = $type;
      $results[$i]['merchant'] = $element->merchant;
      $results[$i]['merchant_id'] = $element->merchant_id;
      $results[$i]['id'] = $element->id;
      $i++;
      }
    }
    $this->response['data'] = $results;
    
    return $this->response();
  }

  public function retrieveList(Request $request){
    $data = $request->all();
    $this->con = $data['condition'];
    // $this->retrieveDB($data);
    $fin = array();
    $result = DB::table('customers as T1')
    ->leftJoin('merchants as T2', 'T2.id', '=', 'T1.merchant_id')
    ->leftJoin('accounts as T3', 'T3.id', '=', 'T2.account_id')
    ->whereNull('T1.deleted_at')
    ->Where(function($quer){
          $quer->Where($this->con[1]['column'], $this->con[1]['clause'], $this->con[1]['value'])
          ->Where(function($query) {
            if($this->con[0]['column'] == 'email') {
              return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value'])
                          ->orWhere('T2.name', $this->con[0]['clause'], $this->con[0]['value']);
            } else if ($this->con[0]['column'] == 'account_type') {
              return $query->Where('T3.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
            } else {
              return $query->Where('T1.'.$this->con[0]['column'], $this->con[0]['clause'], $this->con[0]['value']);
            }
          })
          ->orWhere($this->con[2]['column'], '=', $this->con[2]['value']);
    })
    ->select('T1.merchant', 'T1.merchant_id', 'T2.name', 'T3.account_type', 'T1.email', 'T1.code', 'T1.status', 'T1.id', 'T1.deleted_at')
    ->get();
    if(sizeof($result) > 0){
      $i = 0;
      $this->response['data'] = [];
      foreach ($result as $key) {
        if($key->email != null && $key->merchant_id == null){
          $fin[$i]['name'] = $key->email;
          $accounts = app($this->accountClass)->retrieveByEmail($name);
          $fin[$i]['type'] = $accounts['account_type'];
        }else{
          if(intVal($key->merchant) != intVal($data['merchant_id'])){
            $merchant = app($this->merchantClass)->getByParamsWithAccount('id', $key->merchant);
            $accounts = app($this->accountClass)->retrieveById($merchant->account_id);
            $fin[$i]['type'] = $accounts[0]['account_type'];
            $fin[$i]['name'] = $merchant['name'];
          }
           else {
            $fin[$i]['name']= $key->name;
            $fin[$i]['type'] = $key->account_type;
          }
        }
        $fin[$i]['code'] = $key->code;
        $fin[$i]['status'] = $key->status;
        $fin[$i]['merchant'] = $key->merchant;
        $fin[$i]['merchant_id'] = $key->merchant_id;
        $fin[$i]['id'] = $key->id;
        // if($result[$i]['merchant_id'] != null){
        //   $this->response['data'][] = app($this->merchantClass)->getByParams('id', $result[$i]['merchant_id']);
        // }
        $i++;
      }
    }
    $this->response['data'] = $fin;
    return $this->response();
  }

  public function checkIfExist($merchant, $column, $value){
    $toMerchantId = null;
    $fromEmail = null;
    $isExist = false;
    if($column === 'email'){
      $toAccount = app('Increment\Account\Http\AccountController')->retrieveByEmail($value);
      if($toAccount !== null){
        $toMerchantId =  app('Increment\Marketplace\Http\MerchantController')->getColumnValueByParams('account_id', $toAccount['id'], 'id');
      }
  
      $fromAccount = app('Increment\Marketplace\Http\MerchantController')->getColumnValueByParams('id', $merchant, 'account_id');
      if($fromAccount !== null){
        $account = app('Increment\Account\Http\AccountController')->getAllowedData($fromAccount);
        $fromEmail = $account !== null ? $account['email'] : null;
      }
  
      $asSender = Customer::where('merchant', '=', $merchant)->where(function($query)use($toMerchantId, $value){
        $query->where('email', '=', $value)
        ->orWhere('merchant_id', '=', $toMerchantId);
      })->get();
  
  
      $asReceiver = Customer::where(function($query)use($fromEmail, $merchant, $toMerchantId){
        $query->where('merchant_id', '=', $merchant)
        ->orWhere('email', '=', $fromEmail);
      })->where('merchant', '=', $toMerchantId)->get();
  
      if(sizeof($asSender) > 0){
        $exist = true;
      }else if(sizeof($asReceiver) > 0){
        $exist = true;
      }else{
        $exist = false;
      }
    }else if($column === 'merchant_id'){
      $asReceiver = Customer::where('merchant', '=', $merchant)->where('merchant_id', '=', $value)->get();
      $asSender = Customer::where('merchant', '=', $value)->where('merchant_id', '=', $merchant)->get();
      if(sizeof($asReceiver) > 0){
        $exist = true;
      }else if(sizeof($asSender) > 0){
        $exist = true;
      }else{
        $exist = false;
      }
    }
    return $exist;
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

  public function retrieveAccount(Request $request){
    $data = $request->all();
    $result = Customer::where('merchant', '=', $data['merchant'])->get();
    $temp = false;
    if(sizeof($result) > 0){
      $i=0;
      foreach ($result as $key) {
        if($data['user_type']  === 'USER'){
          $exist = app('Increment\Marketplace\Http\TransferController')->retrieveTransferredByParams($key['merchant_id'], $data['merchant_id']);
          $temp = $exist;
        }else if($data['user_type']  === 'DISTRIBUTOR'){
          $exist = app('Increment\Marketplace\Http\TransferController')->retrieveTransferredByParams($data['merchant'], $data['merchant_id']);
          $temp = $exist;
        }
        $i++;
      }
    }
    $this->response['data'] = $temp;
    return $this->response();
  }
  
  public function selfInvitation($inviterId, $email, $abn){
    $selfInvite = false;
    if($email){
      $receipient = app('Increment\Account\Http\AccountController')->retrieveWithMerchant('email', $email);
      if($receipient){
        if($inviterId == $receipient['merchant']['id']){
          $selfInvite = true;
        }else{
          $selfInvite = false;
        }
      }
    }
    if($abn){
      $receipient = app('Increment\Marketplace\Http\MerchantController')->getByParams('business_code', $abn);
      if($receipient){
        if($inviterId == $receipient['id']){
          $selfInvite = true;
        }else{
          $selfInvite = false;
        }
      }
    }
    return $selfInvite;
  }
}
