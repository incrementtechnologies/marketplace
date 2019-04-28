<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class ProductInventory extends APIModel
{
    protected $table = 'product_inventories';
    protected $fillable = ['product_id', 'qty'];
}
