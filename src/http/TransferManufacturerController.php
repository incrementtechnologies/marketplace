<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Transfer;
use Increment\Marketplace\Models\TransferredProduct;
use Increment\Marketplace\Models\BundledProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransferManufacturerController extends APIController
{
	public $transferredProductsClass = 'Increment\Marketplace\Http\TransferredProductController';
	public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
	public $productClass = 'Increment\Marketplace\Http\ProductController';
	public $productAttrClass = 'Increment\Marketplace\Http\ProductAttributeController';
	public $productTraceClass = 'Increment\Marketplace\Http\ProductTraceController';
	public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
	public $bundledSettingsController = 'Increment\Marketplace\Http\BundledSettingController';
	public $landBlockProductClass = 'App\Http\Controllers\LandBlockProductController';
	public $orderRequestClass = 'Increment\Marketplace\Http\OrderRequestController';
	public $dailyLoadingListClass = 'Increment\Marketplace\Http\DailyLoadingListController';
	public $batcProductClass = 'Increment\Marketplace\Paddock\Http\BatchProductController';
	public $merchantProductClass = 'Increment\Marketplace\Http\MerchantProductController';
	function __construct()
	{
		$this->model = new Transfer();
		$this->localization();

		$this->notRequired = array(
			'order_request_id'
		);
	}

	public function transferOrder(Request $request)
	{

		
		// extract all data from request

		$data = $request->all();
		$data['code'] = $this->generateCode();
		
		// create a transfer row
		$this->insertDB($data);


		$receiverMerchant = app('Increment\Marketplace\Http\MerchantController')->getByParams('id', $data['to']);
		$receiver = app('Increment\Account\Http\AccountController')->getByParamsWithColumns($receiverMerchant['account_id'], ['account_type']);
		if ($this->response['data'] > 0) {
			// On Successful, update the daily order list status to COMPLETED
			app($this->dailyLoadingListClass)->updateByParams('order_request_id', $data['order_request_id'], array(
				'status'  => 'completed',
				'updated_at'  => Carbon::now()
			));

			// On Successful, update the order status to COMPLETED
			app($this->orderRequestClass)->updateByParams($data['order_request_id'], array(
				'status'  => 'completed',
				'date_delivered'  => Carbon::now(),
				'updated_at'  => Carbon::now()
			));

			$products = $data['products'];
			$i = 0;

			// Transfer products to transfer products table
			foreach ($products as $key) {
				// add products here to transferred products table

			}
		}
	}
}
