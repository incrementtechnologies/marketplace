<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\ProductTrace;
use Increment\Marketplace\Models\BundledProduct;
use Increment\Marketplace\Models\TransferredProduct;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TraceController extends APIController
{

    public $productController = 'Increment\Marketplace\Http\ProductController';
    public $transferController = 'Increment\Marketplace\Http\TransferController';
    public $transferredProductController = 'Increment\Marketplace\Http\TransferredProductController';
    public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
    public $bundledSettingController = 'Increment\Marketplace\Http\BundledSettingController';
    public $productAttrClass = 'Increment\Marketplace\Http\ProductAttributeController';
    public $landBlockProductClass = 'App\Http\Controllers\LandBlockProductController';
    public $batchProductClass = 'Increment\Marketplace\Paddock\Http\BatchProductController';

    function __construct()
    {
        $this->model = new ProductTrace();

        $this->notRequired = array(
            'rf', 'nfc', 'manufacturing_date', 'batch_number'
        );

        $this->localization();
    }

    public function activate(Request $request)
    {
        $data = $request->all();


        $this->model = new ProductTrace();
        $this->retrieveDB($data);
        $i = 0;

        foreach ($this->response['data'] as $key) {
            $item = $this->response['data'][$i];
            $this->response['data'][$i]['product'] = app($this->productController)->getProductByParamsWithVariationId('id', $item['product_id'], $item['product_attribute_id']);
            $this->response['data'][$i]['volume'] = app($this->productAttrClass)->getProductUnits('id', $item['product_attribute_id']);
            $item = $this->response['data'][$i];

            if (isset($data['activation'])) {
                if (isset($data['nfc']) && $item['nfc'] !== null && $item['status'] === 'active') {
                    $this->response['data'] = null;
                    $this->response['error'] = 'Tag is already active';
                    return $this->response();
                }
            }
            if (isset($data['nfc']) && ($item['nfc'] == null || $item['nfc'] == '')) {
                $nfcResult = ProductTrace::where('nfc', '=', $data['nfc'])->where('deleted_at', '=', null)->get();
                if (sizeof($nfcResult) > 0) {
                    $this->response['data'] = null;
                    $this->response['error'] = 'Tag is already taken!';
                    return $this->response();
                } else {
                    ProductTrace::where('id', '=', $item['id'])->update(array(
                        'nfc' => $data['nfc'],
                        'updated_at' => Carbon::now(),
                        'status' => 'active'
                    ));
                    $this->response['data'][$i]['nfc'] = $data['nfc'];
                }
            }


            if (isset($data['nfc']) && $item['nfc'] != null && $item['nfc'] != $data['nfc']) {
                $this->response['data'] = null;
                $this->response['error'] = 'Duplicate tag!';
                return $this->response();
            }

            if ($this->checkOwnTrace($item, $data['merchant_id']) == false) {
                $this->response['data'] = null;
                $this->response['error'] = 'You don\'t own this product!';
                return $this->response();
            }
        }

        return $this->response();
    }


    public function checkOwnTrace($trace, $merchantId)
    {
        $bundled = app($this->bundledProductController)->getTrace($trace['id']);
        if ($bundled !== null) {
            $params = array(
                array(function ($query) use ($bundled) {
                    $query->where('payload_value', '=', $bundled['bundled_trace'])
                        ->orWhere('payload_value', '=', $bundled['product_trace']);
                }),
                array('merchant_id', '=', $merchantId),
                array('status', '=', 'active')
            );
            $isOwned = app($this->transferredProductController)->retrieveByCondition($params); //check if product is transferred to you

            $parameter =  array(
                array(function ($query) use ($bundled) {
                    $query->where('payload_value', '=', $bundled['bundled_trace'])
                        ->orWhere('payload_value', '=', $bundled['product_trace']);
                })
            );
            $isTransferred = app($this->transferredProductController)->retrieveByCondition($parameter); // check if product is already transferred to others

            if (sizeof($isOwned) > 0) {
                return true;
            } else if (intval($trace['product']['merchant_id']) == intval($merchantId) && sizeof($isTransferred) <= 0) {
                return true;
            } else {
                return false;
            }
        } else {
            $params = array(
                array('payload_value', '=', $trace['id'])
            );
            $accountType = app('Increment\MarketPlace\Http\MerchantController')->getAccountType($merchantId);
            if ($accountType === 'MANUFACTURER') {
                $params[] =  array('from', '=', $merchantId);
            } else {
                $params[] =  array('to', '=', $merchantId);
                $params[] = array('status', '=', 'active');
            }
            $transferred = app($this->transferredProductController)->retrieveByCondition($params);
            if (sizeof($transferred) > 0) {
                $transferredProduct = $transferred[0];
                if ($transferredProduct['merchant_id'] == $merchantId) {
                    return true;
                } else {
                    return false;
                }
            } else if (intval($trace['product']['merchant_id']) == intval($merchantId)) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
}
