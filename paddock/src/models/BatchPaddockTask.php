<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class BatchPaddockTask extends APIModel
{
    protected $table = 'batch_paddock_tasks';
    protected $fillable = ['batch_id','merchant_id','account_id','paddock_plan_task_id','area'];


    public function batches(){
        return $this->hasOne(Batch::class, 'id', 'batch_id');
    }
}
