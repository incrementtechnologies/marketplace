<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\TransferredProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransferredProductController extends APIController
{

  public $productTraceController = 'Increment\Marketplace\Http\ProductTraceController';
  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
  public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
  function __construct()
  {
    $this->model = new TransferredProduct();
    $this->localization();
    $this->notRequired = array('bundled');
  }

  public function create(Request $request)
  {
    $data = $request->all();
    $merchant = app($this->merchantClass)->getMerchant($data['to']);
    // check the the account type of the receiver is equal to user
    if ($merchant && $merchant['account']['account_type'] == 'USER') {
      if (sizeof($data['products']) > 0) {
        for ($i = 0; $i < sizeof($data['products']); $i++) {
          $type = $data['products'][$i]['type'];
          $traceId = $data['products'][$i]['id'];
          $productId = $data['products'][$i]['product_id'];
          $transferId = $data['transfer_id'];

          if ($type != 'regular') {
            // get the products from the bundled and transfer
            app($this->bundledProductController)->deleteByParams('bundled_trace', $traceId, $transferId);
          } else {
            $array = [];
            $productTrace = app('Increment\Marketplace\Http\ProductTraceController')->getDetailsByParams('id', $traceId, ['product_attribute_id']);
            if ($productTrace) {
              $array[] = array(
                'transfer_id' => $transferId,
                'payload'     => 'product_traces',
                'payload_value' => $traceId,
                'product_id'    => $productId,
                'product_attribute_it' => $productTrace['product_attribute_id'],
                'created_at'    => Carbon::now()
              );
              TransferredProduct::insert($array);
            } else {
              $this->response['data'] = null;
              $this->response['error'] = 'Invalid product trace.';
              return $this->response();
            }
          }
        }
        $this->response['data'] = true;
      } else {
        $this->response['data'] = false;
      }
      return $this->response();
    } else {
      // check if the account type of the mechant is user
      if (sizeof($data['products']) > 0) {
        $array = array();
        for ($i = 0; $i < sizeof($data['products']); $i++) {
          $productTrace = app('Increment\Marketplace\Http\ProductTraceController')->getDetailsByParams('id', $data['products'][$i]['id'], ['product_attribute_id']);
          if ($productTrace) {
            $array[] = array(
              'transfer_id' => $data['transfer_id'],
              'payload'     => 'product_traces',
              'payload_value' => $data['products'][$i]['id'],
              'product_id'    => $data['products'][$i]['product_id'],
              'product_attribute_it' => $productTrace ? $productTrace['product_attribute_id'] : null,
              'created_at'    => Carbon::now()
            );
            TransferredProduct::insert($array);
          } else {
            $this->response['data'] = null;
            $this->response['error'] = 'Invalid product trace.';
            return $this->response();
          }
        }
        TransferredProduct::insert($array);
        $this->response['data'] = true;
      } else {
        $this->response['data'] = false;
      }
      return $this->response();
    }
  }

  public function retrieve(Request $request)
  {
    $data = $request->all();

    $this->model = new TransferredProduct();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if (sizeof($result) > 0) {
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['product_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['payload_value']);
        $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
        $i++;
      }
    }

    return $this->response();
  }

  public function getByParams($column, $value)
  {
    $result = TransferredProduct::where($column, '=', $value)->get();
    if (sizeof($result) > 0) {
      $i = 0;
      foreach ($result as $key) {
        $result[$i]['product_trace_details'] = app($this->productTraceController)->getByParamsDetails('id', $result[$i]['payload_value']);
        $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
        $i++;
      }
    }
    return sizeof($result) > 0 ? $result : null;
  }

  public function getTransferredProduct($productId, $merchantId, $attrId)
  {
    $result = DB::table('transferred_products as T1')
      ->leftJoin('product_traces as T2', 'T1.payload_value', '=', 'T2.id')
      ->where('T1.status', '=', 'active')
      ->where('T1.deleted_at', '=', null)
      ->where('T1.merchant_id', '=', $merchantId)
      ->where('T1.product_attribute_id', '=', $attrId)
      ->select(DB::raw('Count(T1.product_attribute_id) as qty'), 'T2.manufacturing_date')
      ->get();
    return sizeof($result) > 0 ? $result[0] : null;
  }

  public function getTransferredProductInManufacturer($productId, $productAtributeId)
  {
    $result = DB::table('transferred_products as T1')
      ->where('T1.status', '=', 'active')
      ->where('T1.deleted_at', '=', null)
      ->where('T1.product_id', '=', $productId)
      ->where('T1.product_attribute_id', '=', $productAtributeId)
      ->count();
    return $result;
  }

  public function getRemainingProductQty($productId, $merchantId, $productAtributeId)
  {
    $remainingProductInBundled = null;
    $regular = DB::table('product_traces as T2')
      ->where('T2.status', '=', 'active')
      ->where('T2.deleted_at', '=', null)
      ->where('T2.product_id', '=', $productId)
      ->where('T2.product_attribute_id', '=', $productAtributeId)
      ->count();

    $bundled = DB::table('transferred_products as T1')
      ->leftJoin('product_traces as T2', 'T1.payload_value', '=', 'T2.id')
      ->where('T1.status', '=', 'active')
      ->where('T1.payload', '=', 'bundled_trace')
      ->where('T1.deleted_at', '=', null)
      ->where('T1.merchant_id', '=', $merchantId)
      ->where('T1.product_attribute_id', '=', $productAtributeId)
      ->sum('T1.bundled_setting_qty');

    if ($bundled !== 0) {
      $temp = DB::table('transferred_products as T1')
        ->leftJoin('product_traces as T2', 'T1.payload_value', '=', 'T2.id')
        ->where('T1.status', '=', 'active')
        ->where('T1.payload', '=', 'bundled_trace')
        ->where('T1.deleted_at', '=', null)
        ->where('T1.merchant_id', '=', $merchantId)
        ->where('T1.product_attribute_id', '=', $productAtributeId)->get();

      $remainingBundled = app($this->bundledProductController)->getBundledProductsByParams(array(
        array('product_attribute_id', '=', $temp[0]->product_attribute_id),
        array('product_id', '=', $temp[0]->bundled),
        array('deleted_at', '=', null)
      ));
      $remainingProductInBundled = (int)$bundled - (int)$remainingBundled;
    }

    $result = $regular - $remainingProductInBundled;

    return $result;
  }

  public function functionGEtTransferredQtyDisTributor($productId, $merchantId, $productAtributeId)
  {
    $result = DB::table('transferred_products')
      ->where('merchant_id', '=', $merchantId)
      ->where('product_id', '=', $productId)
      ->where('product_attribute_id', '=', $productAtributeId)
      ->select(DB::Raw('count(*) as qty'))
      ->get();

    return $result[0]->qty;
  }

  public function getByParamsOnly($column, $value)
  {
    $result = TransferredProduct::where($column, '=', $value)->get();
    return sizeof($result) > 0 ? $result[0] : null;
  }

  public function getAllByParamsOnly($column, $value)
  {
    $result = TransferredProduct::where($column, '=', $value)->get();
    return sizeof($result) > 0 ? $result : null;
  }

  public function getActivesByParams($column, $value, $offset, $limit)
  {
    $result = TransferredProduct::where($column, '=', $value)->where('status', '=', 'active')->skip($offset)->take($limit)->get();
    return sizeof($result) > 0 ? $result : null;
  }

  public function insert($data)
  {
    TransferredProduct::insert($data);
    return true;
  }

  public function getSize($column, $value, $date)
  {
    $result = TransferredProduct::where($column, '=', $value)->where('created_at', '>', $date)->count();
    return $result;
  }

  public function getSizeNoDate($column, $value)
  {
    $result = TransferredProduct::where($column, '=', $value)->count();
    return $result;
  }

  public function getActiveProductQty($column, $value, $merchantId)
  {
    $result = TransferredProduct::where($column, '=', $value)->where('merchant_id', '=', $merchantId)->where('status', '=', 'active')->count();
    return $result;
  }

  public function getActiveProductQtyInAttribute($productId, $productAtributeId, $merchantId)
  {
    $result = TransferredProduct::where('product_id', '=', $productId)->where('product_attribute_id', '=', $productAtributeId)->where('merchant_id', '=', $merchantId)->where('status', '=', 'active')->count();
    return $result;
  }

  public function getSizeLimit($column, $value, $date)
  {
    $result = TransferredProduct::where($column, '=', $value)->where('created_at', '>', $date)->limit(1)->count();
    return $result;
  }

  public function deleteByParams($id)
  {
    TransferredProduct::where('id', '=', $id)->update(array(
      'deleted_at' => Carbon::now()
    ));
    return true;
  }

  public function deleteByTwoParams($transferId, $payloadValue)
  {
    TransferredProduct::where('transfer_id', '=', $transferId)->where('payload_value', '=', $payloadValue)->update(array(
      'deleted_at' => Carbon::now()
    ));
    return true;
  }
}
