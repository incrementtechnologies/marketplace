<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Merchant extends APIModel
{
    protected $table = 'merchants';
    protected $fillable = ['cpde', 'account_id', 'name', 'prefix', 'logo', 'address', 'status'];
}
