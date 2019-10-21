<?php


namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class BundledProductList extends APIModel
{
    protected $table = 'bundled_product_lists';
    protected $fillable = ['merchant_id', 'product_id'];
}
