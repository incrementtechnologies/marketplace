<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class ProductTrace extends APIModel
{
    protected $table = 'product_traces';
    protected $fillable = ['product_id', 'account_id', 'batch_number', 'manufacturing_date', 'nfc', 'rf', 'status'];
}
