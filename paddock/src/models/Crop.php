<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Crop extends APIModel
{
    protected $table = 'crops';
    protected $fillable = ['merchant_id', 'name'];
}
