<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class PaddockPlan extends APIModel
{
    protected $table = 'paddock_plans';
    protected $fillable = ['paddock_id', 'start_date', 'end_date', 'crop_id'];
}
