<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Paddock extends APIModel
{
    protected $table = 'paddocks';
    protected $fillable = ['merchant_id', 'name', 'area', 'account_id', 'short_description', 'note', 'status', 'arable_area', 'spray_area'];
}
