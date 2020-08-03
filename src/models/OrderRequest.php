<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class OrderRequest extends APIModel
{
    protected $table = 'order_requests';
    protected $fillable = ['code', 'account_id', 'merchant_id', 'merchant_to', 'status', 'date_delivered', 'delivered_by', 'date_of_delivery'];
}
