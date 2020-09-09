<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class DailyLoadingList extends APIModel
{
    protected $table = 'daily_loading_lists';
    protected $fillable = ['code', 'account_id', 'merchant_id', 'order_request_id'];
}
