<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class PaddockPlanTask extends APIModel
{
    protected $table = 'paddock_plans_tasks';
    protected $fillable = ['paddock_plan_id','paddock_id','category','due_date','nickname', 'spray_mix_id', 'status'];
}
