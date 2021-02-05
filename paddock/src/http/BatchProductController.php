<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\BatchProduct;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatchProductController extends APIController
{
    //
    function __construct(){
        $this->model = new BatchProduct();
        $this->notRequired = array();
    }
    
    public function getProductQtyTrace($merchantId, $column, $value, $attr, $productQty){
        $result = DB::table('batch_products as T1')
                ->where($column, '=', $value)
                ->where('merchant_id', '=', $merchantId)
                ->select(DB::raw('Sum(applied_rate) as sum_applied_rate, Count(T1.product_id) as total_products'))->get();
                $result[0]->sum_applied_rate = $result[0]->sum_applied_rate == null ? 0 : $result[0]->sum_applied_rate;
        
        $totalAppliedRateVolume = round(($result[0]->sum_applied_rate / (int)$attr));
        $result[0]->total_product_volume = ($attr * $productQty);
        $result[0]->total_applied_rate_volume = $totalAppliedRateVolume;
        $result[0]->total_remaining_product = ($productQty - $totalAppliedRateVolume);
        return $result;
    }
}
