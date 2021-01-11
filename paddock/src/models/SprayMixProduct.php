<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class SprayMixProduct extends APIModel
{
    protected $table = 'spray_mix_products';
    protected $fillable = ['spray_mix_id', 'product_id', 'rate', 'status', 'units'];
}
