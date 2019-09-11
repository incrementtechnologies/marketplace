<?php

// Checkouts
$route = env('PACKAGE_ROUTE', '').'/checkouts/';
$controller = 'Increment\Marketplace\Http\CheckoutController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Checkout Items
$route = env('PACKAGE_ROUTE', '').'/checkout_items/';
$controller = 'Increment\Marketplace\Http\CheckoutItemController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Coupons
$route = env('PACKAGE_ROUTE', '').'/coupons/';
$controller = 'Increment\Marketplace\Http\CouponController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Merchant
$route = env('PACKAGE_ROUTE', '').'/merchants/';
$controller = 'Increment\Marketplace\Http\MerchantController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Pricings
$route = env('PACKAGE_ROUTE', '').'/pricings/';
$controller = 'Increment\Marketplace\Http\PricingController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Products
$route = env('PACKAGE_ROUTE', '').'/products/';
$controller = 'Increment\Marketplace\Http\ProductController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Product Attributes
$route = env('PACKAGE_ROUTE', '').'/product_attributes/';
$controller = 'Increment\Marketplace\Http\ProductAttributeController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


// Product Attributes
$route = env('PACKAGE_ROUTE', '').'/product_images/';
$controller = 'Increment\Marketplace\Http\ProductImageController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Product Attributes
$route = env('PACKAGE_ROUTE', '').'/product_inventories/';
$controller = 'Increment\Marketplace\Http\ProductInventoryController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

//Orders
$route = env('PACKAGE_ROUTE', '').'/orders/';
$controller = 'Increment\Marketplace\Http\OrderController@';
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_orders', $controller."retrieveOrders");
Route::post($route.'retrieve_items', $controller."retrieveOrderItems");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Shipping Addresses
$route = env('PACKAGE_ROUTE', '').'/shipping_addresses/';
$controller = 'Increment\Marketplace\Http\ShippingAddressController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Wishlists
$route = env('PACKAGE_ROUTE', '').'/wishlists/';
$controller = 'Increment\Marketplace\Http\WishlistController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Product Traces
$route = env('PACKAGE_ROUTE', '').'/product_traces/';
$controller = 'Increment\Marketplace\Http\ProductTraceController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_by_params', $controller."retrieveByParams");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Transferrs
$route = env('PACKAGE_ROUTE', '').'/transfers/';
$controller = 'Increment\Marketplace\Http\TransferController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Transferred Products
$route = env('PACKAGE_ROUTE', '').'/transferred_products/';
$controller = 'Increment\Marketplace\Http\TransferredProductController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");