<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class BatchProduct extends APIModel
{
    protected $table = 'batch_products';
    protected $fillable = ['batch_id','product_id','merchant_id','account_id','product_trace_id','applied_rate'];
}
