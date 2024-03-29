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

    public $merchantClass = 'Increment\Marketplace\Http\MerchantController';

    function __construct()
    {
        $this->model = new ProductTrace();

        $this->notRequired = array(
            'rf',
            'nfc',
            'manufacturing_date',
            'batch_number'
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

            if (isset($data['nfc']) && $item['nfc'] !== null && $item['status'] === 'active') {
                $this->response['data'] = null;
                $this->response['error'] = 'Tag is already active';
                return $this->response();
            }

            if (isset($data['nfc']) && ($item['nfc'] == null || $item['nfc'] == '')) {
                $nfcResult = ProductTrace::where('nfc', '=', $data['nfc'])->where('deleted_at', '=', null)->get();
                if (sizeof($nfcResult) > 0) {
                    $this->response['data'] = null;
                    $this->response['error'] = 'Tag is already taken!';
                    return $this->response();
                } else {
                    ProductTrace::where('id', '=', $item['id'])->update(
                        array(
                            'nfc' => $data['nfc'],
                            'updated_at' => Carbon::now(),
                            'status' => 'active'
                        )
                    );
                    $this->response['data'][$i]['nfc'] = $data['nfc'];
                }
            }

            if (isset($data['nfc']) && $item['nfc'] != null && $item['nfc'] != $data['nfc']) {
                $this->response['data'] = null;
                $this->response['error'] = 'Duplicate tag!';
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
                array(
                    function ($query) use ($bundled) {
                        $query->where('payload_value', '=', $bundled['bundled_trace'])
                            ->orWhere('payload_value', '=', $bundled['product_trace']);
                    }
                ),
                array('merchant_id', '=', $merchantId),
                array('status', '=', 'active')
            );
            $isOwned = app($this->transferredProductController)->retrieveByCondition($params); //check if product is transferred to you

            $parameter = array(
                array(
                    function ($query) use ($bundled) {
                        $query->where('payload_value', '=', $bundled['bundled_trace'])
                            ->orWhere('payload_value', '=', $bundled['product_trace']);
                    }
                )
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
            $accountType = app($this->merchantClass)->getAccountType($merchantId);
            if ($accountType === 'MANUFACTURER') {
                $params[] = array('from', '=', $merchantId);
            } else {
                $params[] = array('to', '=', $merchantId);
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
    }

    public function scanTrace(Request $request)
    {
        $data = $request->all();
        $this->model = new ProductTrace();
        $this->retrieveDB($data);
        $i = 0;


        foreach ($this->response['data'] as $key) {
            $item = $this->response['data'][$i];
              
            $this->response['data'][$i]['product'] = app($this->productController)->getProductByParamsWithVariationId('id', $item['product_id'], $item['product_attribute_id']);
            // $this->response['data'][$i]['volume'] = app($this->productAttrClass)->getProductUnits('id', $item['product_attribute_id']);

            $item = $this->response['data'][$i];

            if ($this->checkOwnTrace($item, $data['merchant_id']) == false) {
                $this->response['data'] = null;
                $this->response['error'] = 'You don\'t own this product!';
                return $this->response();
            }

            // If the tag is a product trace
            $bundled = app($this->bundledProductController)->getBundledTracesByParams('product_trace', $item['id']);

            if($bundled == null){
                // If the tag is a bundled trace
                // get all tags in the bundle
                $this->response['data'][$i]['bundled_product'] = null;
                $traces = app($this->bundledProductController)->getByParamsWithBundledDetails('bundled_trace', $item['id']);
                $this->response['data'][$i]['traces'] = $traces;
                $this->response['data'][$i]['setting_qty'] = $traces && sizeof($traces) > 0 ? sizeof($traces) : 0;
            }else{
                $this->response['data'][$i]['bundled_product'] = $bundled && $bundled['result'] && sizeof($bundled['result']) ? $bundled['result'][0] : null;
                $this->response['data'][$i]['traces']  = $bundled ? $bundled['traces'] : null;
                $this->response['data'][$i]['setting_qty'] = $bundled && $bundled['traces'] && sizeof($bundled['traces']) ? sizeof($bundled['traces']) : 0;
            }
        }

        return $this->response();
    }


    public function createBundledTrace(Request $request)
    {
        $data = $request->all();
        $data['code'] = $this->generateCode();
        $data['status'] = 'active';
        $this->model = new ProductTrace();
        $this->insertDB($data);
        if ($this->response['data'] > 0) {
            // add product to bundled
            $result = app($this->bundledProductController)->insertData($data['products'], $this->response['data']);
            if ($result == false) {
                $this->response['data'] = null;
                $this->response['error'] = 'Unable to manage the request!';
            }
        }
        return $this->response();
    }


    public function generateCode()
    {
        $code = substr(str_shuffle("0123456789012345678901234567890123456789"), 0, 32);
        $codeExist = ProductTrace::where('code', '=', $code)->get();
        if (sizeof($codeExist) > 0) {
            $this->generateCode();
        } else {
            return $code;
        }
    }
}
