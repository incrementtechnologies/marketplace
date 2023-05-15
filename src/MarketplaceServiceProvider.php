<?php

namespace Increment\Marketplace;

use Illuminate\Support\ServiceProvider;

class MarketplaceServiceProvider extends ServiceProvider{

  public function boot(){
    $this->loadMigrationsFrom(__DIR__.'/migrations');
    // $this->loadRoutesFrom(__DIR__.'/routes/web.php');
  }

  public function register(){
  }
}
