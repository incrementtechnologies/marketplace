<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Customer extends APIModel
{
    protected $table = 'customers';
    protected $fillable = ['code', 'merchant', 'merchant_id', 'status'];
}
