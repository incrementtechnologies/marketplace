<?php

// Paddock
$route = env('PACKAGE_ROUTE', '').'/paddocks/';
$controller = 'Increment\Imarket\Paddock\Http\PaddockController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Paddock Plans
$route = env('PACKAGE_ROUTE', '').'/paddock_plans/';
$controller = 'Increment\Imarket\Paddock\Http\PaddockPlanController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Paddock Plan Tasks
$route = env('PACKAGE_ROUTE', '').'/paddock_plan_tasks/';
$controller = 'Increment\Imarket\Paddock\Http\PaddockPlanTaskController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

//Machines
$route = env('PACKAGE_ROUTE', '').'/machines/';
$controller = 'Increment\Imarket\Paddock\Http\MachineController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Spray Mixes
$route = env('PACKAGE_ROUTE', '').'/spray_mixes/';
$controller = 'Increment\Imarket\Paddock\Http\SprayMixController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Spray Mix Products
$route = env('PACKAGE_ROUTE', '').'/spray_mix_products/';
$controller = 'Increment\Imarket\Paddock\Http\SprayMixProductController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Batches
$route = env('PACKAGE_ROUTE', '').'/batches/';
$controller = 'Increment\Imarket\Paddock\Http\BatchController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Batch Paddock Tasks
$route = env('PACKAGE_ROUTE', '').'/batch_paddock_tasks/';
$controller = 'Increment\Imarket\Paddock\Http\BatchPaddockTaskController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


//Batch Products
$route = env('PACKAGE_ROUTE', '').'/batch_products/';
$controller = 'Increment\Imarket\Paddock\Http\BatchProductsController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

//Crops
$route = env('PACKAGE_ROUTE', '').'/crops/';
$controller = 'Increment\Imarket\Paddock\Http\CropController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");












