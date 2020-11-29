<?php

namespace Increment\Imarket\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class SprayMix extends APIModel
{
    protected $table = 'spray_mixes';
    protected $fillable = ['merchant_id', 'name', 'short_description', 'crop_id', 'application_rate', 'minimum_rate', 'maximum_rate', 'status'];
}
