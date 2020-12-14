<?php

// Paddock
$route = env('PACKAGE_ROUTE', '').'/paddocks/';
$controller = 'Increment\Marketplace\Paddock\Http\PaddockController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Paddock Plans
$route = env('PACKAGE_ROUTE', '').'/paddock_plans/';
$controller = 'Increment\Marketplace\Paddock\Http\PaddockPlanController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Paddock Plan Tasks
$route = env('PACKAGE_ROUTE', '').'/paddock_plan_tasks/';
$controller = 'Increment\Marketplace\Paddock\Http\PaddockPlanTaskController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

//Machines
$route = env('PACKAGE_ROUTE', '').'/machines/';
$controller = 'Increment\Marketplace\Paddock\Http\MachineController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Spray Mixes
$route = env('PACKAGE_ROUTE', '').'/spray_mixes/';
$controller = 'Increment\Marketplace\Paddock\Http\SprayMixController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_rescent', $controller."retrieveRescent");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Spray Mix Products
$route = env('PACKAGE_ROUTE', '').'/spray_mix_products/';
$controller = 'Increment\Marketplace\Paddock\Http\SprayMixProductController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Batches
$route = env('PACKAGE_ROUTE', '').'/batches/';
$controller = 'Increment\Marketplace\Paddock\Http\BatchController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Batch Paddock Tasks
$route = env('PACKAGE_ROUTE', '').'/batch_paddock_tasks/';
$controller = 'Increment\Marketplace\Paddock\Http\BatchPaddockTaskController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Batch Products
$route = env('PACKAGE_ROUTE', '').'/batch_products/';
$controller = 'Increment\Marketplace\Paddock\Http\BatchProductsController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

//Crops
$route = env('PACKAGE_ROUTE', '').'/crops/';
$controller = 'Increment\Marketplace\Paddock\Http\CropController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

//Dashboard
$route = env('PACKAGE_ROUTE','').'/dashboard/';
$controller = 'Increment\Marketplace\Paddock\Http\DashboardController@';
Route::post($route.'retrieve', $controller."retrieveDashboard");











