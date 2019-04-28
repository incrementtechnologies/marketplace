<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class ShippingAddress extends APIModel
{
    protected $table = 'shipping_addresses';
    protected $fillable = ['checkout_id', 'payload', 'payload_value', 'contact_number', 'notes'];
}
