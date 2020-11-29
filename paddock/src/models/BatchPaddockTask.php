<?php

namespace Increment\Imarket\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class BatchPaddockTask extends APIModel
{
    protected $table = 'batch_paddock_tasks';
    protected $fillable = ['batch_id','merchant_id','account_id','paddock_plan_task_id','area'];
}
