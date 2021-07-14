<?php

namespace App;

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;

class MerchantProduct extends APIModel
{
    protected $table = 'merchant_products';
    protected $fillable = ['product_id', 'merchant_id', 'product_attribute_id'];
}
