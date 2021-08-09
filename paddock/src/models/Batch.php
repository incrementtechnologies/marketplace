<?php

namespace Increment\Marketplace\Paddock\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Batch extends APIModel
{
    protected $table = 'batches';
    protected $fillable = ['spray_mix_id', 'machine_id', 'merchant_id', 'account_id', 'notes', 'status', 'water', 'session', 'applied_rate'];
}
