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
Route::post($route.'retrieve_all', $controller."retrieveAll");
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
Route::post($route.'retrieve_basic', $controller."retrieveBasic");
Route::post($route.'retrieve_bundled_mobile', $controller."retrieveBundledMobile");
Route::post($route.'retrieve_published_products', $controller."retrieveProductPublished");
Route::post($route.'retrieve_bundled', $controller."retrieveBundled");
Route::post($route.'retrieve_bundled_agrisend', $controller."retrieveBundledAgrisend");
Route::post($route.'retrieve_variation', $controller."retrieveVariation");
Route::post($route.'retrieve_basic_mobile', $controller."retrieveBasicMobile");
Route::post($route.'retrieve_mobile', $controller."retrieveMobile");
Route::post($route.'retrieve_with_order_number', $controller."retrieveWithOrderNumber");
Route::post($route.'retrieve_featured_images', $controller."retrieveFeaturedImage");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::post($route.'file_upload', $controller."fileUpload");
Route::post($route.'get_by_categories', $controller."getByCategories");
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
Route::post($route.'retrieve_product_inventory', $controller."getProductInvetory");
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
Route::post($route.'create_bundled', $controller."createBundled");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_with_attribute', $controller."retrieveWithAttribute");
Route::post($route.'retrieve_with_attribute_mobile', $controller."retrieveWithAttributeMobile");
Route::post($route.'retrieve_with_traces', $controller."retrieveProductTraceAsInventory");
Route::post($route.'retrieve_by_params', $controller."retrieveByParams");
Route::post($route.'retrieve_nfc', $controller."retrieveNFC");
Route::post($route.'retrieve_by_params_end_user', $controller."retrieveByParamsEndUser");
Route::post($route.'retrieve_by_bundled', $controller."retrieveByBundled");
Route::post($route.'retrieve_by_bundled_agrisend', $controller."retrieveByBundledAgrisend");
Route::post($route.'retrieve_with_transfer', $controller."retrieveWithTransfer");
Route::post($route.'retrieve_inactive', $controller.'retrieveInactive');
Route::post($route.'update', $controller."update");
Route::post($route.'update_trace', $controller."updateTrace");
Route::post($route.'delete', $controller."delete");
Route::post($route.'delete_all', $controller."deleteAll");
Route::get($route.'test', $controller."test");

// Product Traces
$route = env('PACKAGE_ROUTE', '').'/product_traces/';
$controller = 'Increment\Marketplace\Http\TraceController@';
Route::post($route.'activate', $controller."activate");


// Transferrs
$route = env('PACKAGE_ROUTE', '').'/transfers/';
$controller = 'Increment\Marketplace\Http\TransferController@';
Route::post($route.'create', $controller."create");
Route::post($route.'create_deliveries', $controller."createDeliveries");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'basic_retrieve', $controller."basicRetrieve");
Route::post($route.'retrieve_by_pagination', $controller."retrieveByPagination");
Route::post($route.'retrieve_product_title', $controller."retrieveProductTitle");
Route::post($route.'retrieve_by_consignment_pagination', $controller."retrieveByConsignmentsPagination");
Route::post($route.'retrieve_products_first_level', $controller."retrieveProductsFirstLevel");
Route::post($route.'retrieve_products_first_level_end_user', $controller."retrieveProductsFirstLevelEndUser");
Route::post($route.'retrieve_products_second_level_end_user', $controller."retrieveProductsSecondLevelEndUser");
Route::post($route.'retrieve_products_second_level', $controller."retrieveProductsSecondLevel");
Route::post($route.'retrieve_consignments', $controller."retrieveConsignments");
Route::post($route.'retrieve_allowed_only', $controller."retrieveAllowedOnly");
Route::post($route.'retrieve_transferred_items', $controller."retrieveTransferredItems");
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


// Bundled Products
$route = env('PACKAGE_ROUTE', '').'/bundled_products/';
$controller = 'Increment\Marketplace\Http\BundledProductController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'recover_deleted', $controller."recoverDeleted");
Route::post($route.'update_deleted_at', $controller."updateDeletedAt");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


// Bundled Products
$route = env('PACKAGE_ROUTE', '').'/bundled_settings/';
$controller = 'Increment\Marketplace\Http\BundledSettingController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_with_trace', $controller."retrieveWithTrace");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Customers
$route = env('PACKAGE_ROUTE', '').'/customers/';
$controller = 'Increment\Marketplace\Http\CustomerController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_list', $controller."retrieveList");
Route::post($route.'retrieve_by_filter', $controller."retrieveAll");
Route::post($route.'retrieve_accounts', $controller."retrieveAccount");
Route::post($route.'retrieve_allowed_only', $controller."retrieveAllowedOnly");
Route::post($route.'resend', $controller."resend");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Order Requests
$route = env('PACKAGE_ROUTE', '').'/order_requests/';
$controller = 'Increment\Marketplace\Http\OrderRequestController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_by_level', $controller."retrieveSecondLevel");
Route::post($route.'retrieve_all', $controller."retrieveAllOrders");
Route::post($route.'retrieve_order_details', $controller."retreiveOrderDetails");
Route::post($route.'retrieve_orders', $controller."retrieveMobile");
Route::post($route.'retrieve_orders_by_params', $controller."retrieveMobileByParams");
Route::post($route.'retrieve_orders_dashboard', $controller."retrieveMobileDashboard");
Route::post($route.'new_update', $controller."newUpdate");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Order Request Items
$route = env('PACKAGE_ROUTE', '').'/order_request_items/';
$controller = 'Increment\Marketplace\Http\OrderRequestItemController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


// Daily Loading Lists
$route = env('PACKAGE_ROUTE', '').'/daily_loading_lists/';
$controller = 'Increment\Marketplace\Http\DailyLoadingListController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_basic', $controller."retrieveBasic");
Route::post($route.'retrieve_summary_total', $controller."retrieveSummaryTotal");
Route::post($route.'approved', $controller."approved");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");