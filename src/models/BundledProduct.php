<?php


namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class BundledProduct extends APIModel
{
    protected $table = 'bundled_products';
    protected $fillable = ['account_id', 'product_id', 'bundled_trace', 'product_trace','product_on_settings'];
}
