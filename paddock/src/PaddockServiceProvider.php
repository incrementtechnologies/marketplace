<?php

namespace Increment\Marketplace\Paddock;

use Illuminate\Support\ServiceProvider;

class PaddockServiceProvider extends ServiceProvider{

  public function boot(){
    $this->loadMigrationsFrom(__DIR__.'/migrations');
    $this->loadRoutesFrom(__DIR__.'/routes/web.php');
  }

  public function register(){
  }
}