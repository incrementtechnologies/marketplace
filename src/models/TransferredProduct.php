<?php

namespace Increment\Marketplace\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class TransferredProduct extends APIModel
{
    protected $table = 'transferred_products';
    protected $fillable = ['transfer_id', 'payload', 'payload_value'];
}
