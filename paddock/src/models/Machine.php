<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Machine extends APIModel
{
    protected $table = 'machines';
    protected $fillable = ['merchant_id', 'name', 'manufacturer', 'model', 'type'];
}
