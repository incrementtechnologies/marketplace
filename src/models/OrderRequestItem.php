<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class OrderRequestItem extends APIModel
{
    protected $table = 'order_request_items';
    protected $fillable = ['order_request_id', 'product_id', 'qty'];
}
