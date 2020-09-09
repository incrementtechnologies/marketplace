<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\DailyLoadingList;
use Carbon\Carbon;
class DailyLoadingListController extends APIController
{

  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';

  function __construct(){
    $this->model = new DailyLoadingList();
  }

  public function create(Request $request){
    $data = $request->all();
    $data['code'] = $this->generateCode();
    $this->model = new DailyLoadingList();
    $this->insertDB($data);
    return $this->response();
  }

  public function generateCode(){
    $code = 'DLL_'.substr(str_shuffle($this->codeSource), 0, 61);
    $codeExist = DailyLoadingList::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }
}
