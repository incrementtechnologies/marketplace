<?php

namespace Increment\Marketplace\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Increment\Marketplace\Models\TransferredProduct;
use Carbon\Carbon;
class TransferredProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $products;
    public $transferId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($products, $transferId)
    {
        $this->products = $products;
        $this->transferId = $transferId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        for ($i=0; $i < sizeof($this->products) - 1; $i++) { 
            $model = new TransferredProduct();
            $model->transfer_id = $this->transferId;
            $model->payload = 'product_traces';
            $model->payload_value = $this->products[$i]['id'];
            $model->created_at = Carbon::now();
            $model->save();
        }
    }
}
