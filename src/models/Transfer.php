<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Transfer extends APIModel
{
    protected $table = 'transfers';
    protected $fillable = ['account_id', 'from', 'to'];
}
