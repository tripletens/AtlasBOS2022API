<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\DealerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/check', function () {
    echo 'tsersrss';
});

// Route::group(
//     [
//         'middleware' => 'api',
//         // 'prefix' => 'auth',
//         'namespace' => 'App\Http\Controllers',
//     ],
//     function ($router) {
//         // Route::post('/login', 'AuthController@login');
//         // Route::post('/register', 'AuthController@register');

//         Route::post('/login', 'DealerController@login');
//     }
// );

///////////////// Admin /////////////
Route::group(
    [
        'namespace' => 'App\Http\Controllers',
    ],
    function () {
        Route::post('/admin-login', 'AdminController@login');
        Route::post('/register-dealer', 'AdminController@register_dealer');
        Route::post('/register-admin', 'AdminController@register_admin');

        Route::get(
            '/admin/logged-in-dealers',
            'AdminController@all_logged_in_dealers'
        );

        Route::get(
            '/admin/not-logged-in-dealers',
            'AdminController@all_not_logged_in_dealers'
        );

        Route::post(
            '/admin/upload-replaced-dealer-data',
            'AdminController@upload_replaced_dealer_data'
        );

        Route::get(
            '/admin/fetch-dealer-data-by-account/{dealer_id}',
            'AdminController@fetch_dealers_by_account'
        );

        Route::get(
            '/admin/fetch-all-dealer-service-parts',
            'AdminController@fetch_all_service_parts'
        );

        Route::post(
            '/update-vendor-name-vendor-logo',
            'AdminController@update_vendor_name_vendor_logo'
        );

        Route::post(
            '/admin/update-short-note-url',
            'AdminController@update_short_note_url_upload'
        );

        Route::post(
            '/admin/upload-promo-flyer-updated',
            'AdminController@upload_promo_flyer'
        );

        Route::post(
            '/admin/upload-new-products',
            'AdminController@upload_new_products'
        );

        Route::post(
            '/admin/upload-dealer-excel',
            'AdminController@upload_dealer_excel'
        );

        Route::post(
            '/admin/upload-regular-products',
            'AdminController@upload_regular_products'
        );

        Route::post(
            '/admin/upload-assorted-products',
            'AdminController@upload_product_assorted'
        );

        Route::post(
            '/admin/upload-special-products',
            'AdminController@upload_product_special'
        );

        Route::post(
            '/admin/upload-catalogue-products',
            'AdminController@upload_catalogue_products'
        );

        Route::post(
            '/admin/upload-carded-products',
            'AdminController@upload_carded_products'
        );

        Route::post(
            '/admin/upload-service-products',
            'AdminController@upload_service_products'
        );

        Route::get(
            '/admin/get-location-dealers',
            'AdminController@dealer_location_filter'
        );

        Route::get('/admin/fix-pro-type', 'AdminController@update_pro_type');

        Route::get(
            '/admin/close-bos-program',
            'AdminController@close_bos_program'
        );
        Route::get(
            '/admin/open-bos-program',
            'AdminController@open_bos_program'
        );

        Route::get(
            '/deactivate_admin/{id}',
            'AdminController@deactivate_admin'
        );
        Route::get('/activate_admin/{id}', 'AdminController@activate_admin');

        // Route::post('/admin/import-file', 'AdminController@bulk_upload');
        Route::post(
            '/upload_product_csv',
            'AdminController@upload_product_csv'
        );
        Route::post(
            '/add_promotional_ad',
            'AdminController@add_promotional_ad'
        );
        Route::post(
            '/edit_promotional_ad',
            'AdminController@edit_promotional_ad'
        );
        Route::post('/add_category', 'CategoryController@store');
        Route::get('/category/{id}', 'CategoryController@show');
        Route::post('/edit_category/{id}', 'CategoryController@update');
        Route::get('/delete_category/{id}', 'CategoryController@destroy');
        Route::get(
            '/fetch_all_promotional_ad',
            'AdminController@fetch_all_promotional_ad'
        );
        Route::get(
            '/fetch_one_promotional_ad/{id}',
            'AdminController@fetch_one_promotional_ad'
        );
        Route::get(
            '/fetch_one_promotional_ad_by_category_id_type/{type}/{category_id}',
            'AdminController@fetch_one_promotional_ad_by_category_id_type'
        );

        Route::get(
            '/available_catalogue_orders',
            'AdminController@available_catalogue_orders'
        );
        Route::get('/fetch_dealers', 'AdminController@fetch_dealers');

        Route::post(
            '/update_short_note_url',
            'AdminController@update_short_note_url'
        );

        Route::get(
            '/deactivate_dealer/{dealer_id}',
            'AdminController@deactivate_dealers'
        );
        Route::get(
            '/activate_dealer/{dealer_id}',
            'AdminController@activate_dealers'
        );

        Route::get(
            '/send_dealer_details_email/{dealer_email}',
            'AdminController@send_dealer_details_email'
        );
        Route::get('/fetch_all_products', 'AdminController@fetch_all_products');
        Route::get(
            '/sort_dealer_by_location/{location}',
            'AdminController@sort_dealer_by_location'
        );

        Route::post(
            '/edit_catalogue_order',
            'AdminController@edit_catalogue_order'
        );

        Route::get(
            '/admin-search-product-by-category/{category}',
            'AdminController@search_category'
        );

        Route::get('/fetch_all_category', 'AdminController@fetch_all_category');
        Route::get(
            '/fetch_all_promo_category',
            'AdminController@fetch_all_promo_category'
        );

        Route::get(
            '/fetch_dealer_catalogue_orders/{dealer_id}',
            'AdminController@fetch_dealer_catalogue_orders'
        );

        Route::get(
            '/delete_product/{product_id}',
            'AdminController@delete_product'
        );
        Route::get(
            '/restore_product/{product_id}',
            'AdminController@restore_product'
        );

        ////  Route::get('/fetch-all-orders', 'AdminController@fetch_all_orders');
        Route::get(
            '/fetch-all-dealers-with-order',
            'AdminController@fetch_all_dealers_with_orders'
        );

        Route::get(
            '/fetch_dealer_by_account_id/{account_id}',
            'AdminController@fetch_dealer_by_account_id'
        );

        Route::post('/update_product', 'AdminController@update_product');
        // Route::get(
        //     '/delete_catalogue_order/{dealer_id}',
        //     'AdminController@delete_catalogue_order'
        // );

        // delete_carded_product

        Route::get(
            '/delete-catalogue-order-admin/{dealer_id}/{atlas_id}',
            'AdminController@delete_catalogue_order'
        );
        Route::get(
            '/delete-service-part-admin/{dealer_id}/{atlas_id}',
            'AdminController@delete_service_part'
        );

        Route::get(
            '/delete-carded-product-admin/{dealer_id}/{atlas_id}',
            'AdminController@delete_carded_product'
        ); // working
        

        Route::get(
            '/restore_catalogue_order/{dealer_id}',
            'AdminController@restore_catalogue_order'
        );

        Route::get('/no_of_dealers', 'AdminController@no_of_dealers');
        Route::get('/no_of_products', 'AdminController@no_of_products');
        Route::get(
            '/no_of_catalogue_orders',
            'AdminController@no_of_catalogue_orders'
        );

        Route::get('/admin-dashboard', 'AdminController@admin_dashboard');
        Route::get('/get-all-category', 'AdminController@get_all_category');
        Route::get(
            '/get-products-by-category/{category}',
            'AdminController@get_products_by_category'
        );

        Route::get(
            '/recent-order-dashboard',
            'AdminController@recent_order_dashboard'
        );
        Route::get('/view-order/{id}', 'AdminController@view_order');

        Route::get(
            '/admin-view-dealer-cart/{id}',
            'AdminController@admin_view_dealer_order'
        );

        Route::get(
            '/view-order-by-dealer-id/{dealer_id}',
            'AdminController@view_order_by_dealer_id'
        );

        Route::get('/get-logged', 'AdminController@get_dealer_log');

        Route::post(
            '/change_dealer_password',
            'AdminController@change_dealer_password'
        );

        Route::post(
            '/add-carded-product-admin',
            'AdminController@add_carded_product'
        );
        Route::post(
            '/add-service-part-admin',
            'AdminController@add_service_part'
        );

        Route::post('/edit-dealer-admin', 'AdminController@edit_dealer');

        Route::post(
            '/edit-carded-product-admin',
            'AdminController@edit_carded_product'
        );

        Route::post('/edit_order', 'AdminController@edit_order');

        Route::get(
            '/delete_order/{dealer_id}/{atlas_id}',
            'AdminController@delete_order'
        );

        Route::get(
            '/fetch_all_carded_products',
            'AdminController@fetch_all_carded_products'
        );

        Route::post('/register-branch', 'AdminController@register_branch');

        Route::post('/upload-dealer-csv', 'AdminController@dealer_upload');

        Route::get(
            '/fetch_service_parts_by_dealer_id/{dealer_id}',
            'AdminController@fetch_service_parts_by_dealer_id'
        );

        Route::get(
            '/fetch_carded_products_by_dealer_id/{dealer_id}',
            'AdminController@fetch_carded_products_by_dealer_id'
        );

        Route::post('/dealer-csv-upload', 'AdminController@dealer_upload');

        Route::get('/export-excel-query', 'AdminController@export_excel_query');

        Route::get(
            '/delete_dealer/{dealer_id}',
            'AdminController@delete_dealer'
        );

        Route::get(
            '/export_carded_products/{from}/{to}',
            'AdminController@export_carded_products'
        );

        Route::get(
            '/export_service_parts/{from}/{to}',
            'AdminController@export_service_parts'
        );

        Route::get(
            '/export_catalogue_orders/{from}/{to}',
            'AdminController@export_catalogue_orders'
        );

        Route::get(
            '/export-catalogue-data',
            'AdminController@export_all_catalogue_orders'
        );

        Route::get(
            '/export-carded-data',
            'AdminController@export_all_carded_orders'
        );

        Route::get(
            '/export-service-parts-data',
            'AdminController@export_all_service_parts_orders'
        );

        Route::get('/fetch_locations', 'AdminController@fetch_locations');

        Route::get(
            '/rollback-order-status/{id}',
            'AdminController@rollback_order_status'
        );

        Route::get(
            '/change-product-status/{id}',
            'AdminController@change_product_status'
        );

        Route::post(
            '/update-product-img',
            'AdminController@update_product_img'
        );

        Route::post(
            '/update-vendor-logo',
            'AdminController@update_vendor_logo'
        );

        Route::get(
            '/admin-send-order-mail/{id}',
            'AdminController@admin_send_order_mail'
        );

        Route::get(
            '/admin-download-dealer-order/{id}',
            'AdminController@admin_download_dealer_order'
        );

        Route::get('/fetch_all_admins', 'AdminController@fetch_all_admins');

        Route::get('/fetch-all-orders', 'AdminController@fetch_all_orders');

        Route::get(
            '/end-all-booking-program',
            'AdminController@end_booking_program'
        );

        Route::get(
            '/active-dealer-booking/{id}',
            'AdminController@active_dealer_booking'
        );
    }
);

///////////////// Dealer /////////////
Route::group(
    ['namespace' => 'App\Http\Controllers', 'middleware' => 'cors'],
    function () {
        // Route::get('/dashboard/dealer', 'DealerController@dealer_de');

        Route::post('/dealer-login', 'DealerController@login');
        Route::get('/dashboard/{dealer}', 'DealerController@dashboard');
        Route::post('/add-product', 'DealerController@add_product');
        Route::get('/fetch-all-product', 'DealerController@fetch_all_products');
        Route::get(
            '/search-product-by-category/{category}',
            'DealerController@search_category'
        );
        Route::get('/all-category', 'CategoryController@index');
        Route::get(
            '/quick-search-category/{category_name}/{value}',
            'DealerController@quick_search_product'
        );
        Route::post('/send-order-email', 'DealerController@sendOrderMail');
        Route::get(
            '/search-product/{value}',
            'DealerController@search_product'
        );

        Route::get(
            '/search-product-by-type-category/{value}/{type}',
            'DealerController@search_product_by_type_category'
        );

        Route::get(
            '/check-order-product/{value}/{type}',
            'DealerController@validate_extra_products'
        );

        Route::get(
            '/search-product-type/{value}',
            'DealerController@search_product_type'
        );

        Route::get(
            '/search-product-type-carded-products/{value}',
            'DealerController@search_product_type_carded_product'
        );

        Route::get(
            '/fetch_all_promotional_ad',
            'DealerController@fetch_all_promotional_ad'
        );
        Route::get(
            '/fetch-category-promotions/{id}',
            'DealerController@fetch_promotion_by_category'
        );
        Route::post(
            '/add-catalogue-order',
            'DealerController@add_catalogue_order'
        );
        Route::get('/check-atlas/{id}', 'DealerController@check_atlas_id');
        // Route::post('/save-catalogue', 'DealerController@add_catalogue_order');
        // Route::get('/send-html/{id}', 'DealerController@send_pdf');
        Route::get(
            '/get-dealer-orders/{id}',
            'DealerController@get_dealer_orders'
        );
        Route::post('/log-dealer', 'DealerController@save_dealer_login_log');

        Route::get('/dashboard/{dealer}', 'DealerController@dashboard');
        Route::post('/add-product', 'DealerController@add_product');
        Route::get('/fetch-all-product', 'DealerController@fetch_all_products');

        Route::get('/all-category', 'CategoryController@index');
        Route::get(
            '/quick-search-category/{category_name}/{value}',
            'DealerController@quick_search_product'
        );
        Route::post('/send-order-email', 'DealerController@sendOrderMail');
        Route::get(
            '/search-product/{value}',
            'DealerController@search_product'
        );
        Route::get(
            '/fetch_all_promotional_ad',
            'DealerController@fetch_all_promotional_ad'
        );
        Route::get(
            '/fetch-category-promotions/{id}',
            'DealerController@fetch_promotion_by_category'
        );
        Route::post(
            '/add-catalogue-order',
            'DealerController@add_catalogue_order'
        );
        Route::get('/check-atlas/{id}', 'DealerController@check_atlas_id');
        // Route::post('/save-catalogue', 'DealerController@add_catalogue_order');
        // Route::get('/send-html/{id}', 'DealerController@send_pdf');
        Route::get(
            '/get-dealer-orders/{id}',
            'DealerController@get_dealer_orders'
        );
        Route::post('/log-dealer', 'DealerController@save_dealer_login_log');

        Route::get(
            '/delete-carded-product/{dealer_id}/{atlas_id}',
            'DealerController@delete_carded_product'
        ); // working
        Route::get(
            '/restore-carded-product/{id}',
            'DealerController@restore_carded_product'
        ); // working

        Route::get(
            '/fetch_carded_products_by_id/{atlas_id}',
            'DealerController@fetch_carded_products_by_id'
        );

        Route::post('/edit-service-part', 'DealerController@edit_service_part');
        Route::get(
            '/delete-service-part/{dealer_id}/{atlas_id}',
            'DealerController@delete_service_part'
        );

        Route::get(
            '/restore-service-part/{id}',
            'DealerController@restore_service_part'
        );
        Route::get(
            '/fetch_all_service_parts',
            'DealerController@fetch_all_service_parts'
        );
        Route::get(
            '/fetch_service_parts_by_id/{atlas_id}',
            'DealerController@fetch_service_parts_by_id'
        );

        Route::post(
            '/add-carded-product-dealer',
            'DealerController@add_carded_product'
        );
        Route::post(
            '/edit_carded_product',
            'DealerController@edit_carded_product'
        );
        Route::get(
            '/delete_carded_product/{id}',
            'DealerController@delete_carded_product'
        );
        Route::get(
            '/restore_carded_product/{id}',
            'DealerController@restore_carded_product'
        );

        Route::post(
            '/add-service-part-dealer',
            'DealerController@add_service_part'
        );
        Route::get(
            '/fetch_all_new_products',
            'DealerController@fetch_all_new_products'
        );

        Route::get(
            '/delete_all_cart_items/{dealer_id}',
            'DealerController@delete_all_cart_items'
        );

        Route::post('/store-user-cart', 'DealerController@store_user_cart');
        Route::post('edit-user-cart/', 'DealerController@edit_user_cart');

        Route::get(
            '/delete_catalogue_order_dealer/{dealer_id}/{atlas_id}',
            'DealerController@delete_catalogue_order'
        );
        
        Route::get(
            '/restore_catalogue_order_dealer/{dealer_id}',
            'DealerController@restore_catalogue_order'
        );

        Route::get(
            '/get-user-cart-item/{id}',
            'DealerController@get_user_cart'
        );

        Route::get(
            '/get-recent-item-in-cart/{id}',
            'DealerController@recent_item_in_cart'
        );

        Route::get(
            '/remove-item-cart/{dealer}/{id}',
            'DealerController@remove_item_cart'
        );

        Route::get(
            '/submit_carded_products/{dealer}',
            'DealerController@submit_carded_products'
        );

        Route::get(
            '/submit_service_parts/{dealer}',
            'DealerController@submit_service_parts'
        );

        Route::get('/download-pdf/{id}', 'DealerController@download_pdf');

        Route::get('/fetch_dealer_with_pending_order_dealer/{dealer_id}', 'DealerController@fetch_dealer_with_pending_order_dealer');

        Route::get('/download-pending-order-pdf/{dealer_id}', 'DealerController@download_pending_order_pdf');
        
        Route::get(
            '/submit_catalogue_order/{dealer}',
            'DealerController@submit_catalogue_order'
        );

        Route::get('/cart_count/{dealer}', 'DealerController@cart_count');

        Route::get(
            '/get-dealer-order-summary/{dealer}',
            'DealerController@get_dealer_order_summary'
        );

        Route::get(
            '/remove-item-cart/{dealer}/{id}/{grouping}',
            'DealerController@remove_item_cart'
        );
        Route::get('/send-order-mail/{id}', 'DealerController@send_order_mail');
        Route::get('/submit-order/{id}', 'DealerController@user_submit_order');

        Route::get(
            '/edit-user-cart-item/{dealer}/{proId}/{qty}/{price}/{unitPrice}',
            'DealerController@edit_user_cart_item'
        );
        Route::get(
            '/check_is_new/{created_at}/{number_of_days}/{atlas_id}',
            'DealerController@check_if_its_new'
        );
        Route::get(
            '/view_dealer_order_by_acct_id/{account_id}',
            'DealerController@view_dealer_order_by_acct_id'
        );

        Route::get(
            '/send-order-to-mail/{id}',
            'DealerController@send_order_to_mail'
        );

        // Get all loggedin dealers
        Route::get(
            '/get-all-loggedin-dealers',
            'DealerController@get_all_loggedin_dealers'
        );

        // submit carded products to cart
        Route::post(
            '/add-carded-product-to-cart',
            'DealerController@add_carded_product_to_cart'
        );

        // submit other products to cart i.e carded, catalogue and carded products
        Route::post(
            '/add-other-product-type-to-cart',
            'DealerController@add_other_product_type_to_cart'
        );

        // unautheticated routes
        // reset dealer email
        Route::post('/reset-dealer-password', [
            DealerController::class,
            'reset_dealer_password',
        ]);

        // send code to user
        Route::post('/reset-password-send-code-email', [
            DealerController::class,
            'reset_password_send_code_email',
        ]);

        // verify code password reset
        Route::get('/reset-password-verify-code-email/{email}/{account_id}/{code}', [
            DealerController::class,
            'reset_password_verify_code_email',
        ]);

        // export_all_cart_orders
        Route::get('/export-all-cart-orders', [
            DealerController::class,
            'export_all_cart_orders',
        ]);

        // TEST
        Route::get('/test', [DealerController::class, 'test']);
    }
);

///////////////// Branch /////////////
Route::group(['namespace' => 'App\Http\Controllers'], function () {
    Route::post('/branch-login', 'BranchController@login');
    Route::get(
        '/fetch_branch_by_id/{id}',
        'BranchController@fetch_branch_by_id'
    );

    Route::get(
        '/branch/fetch-all-new-products',
        'BranchController@fetch_all_new_product_branch'
    );

    Route::get(
        '/branch-get-dealer-order-summary/{id}',
        'BranchController@get_dealer_order_summary'
    );

    Route::get('/fetch_all_branches', 'BranchController@fetch_all_branches');

    Route::get('/download-pdf-branch/{id}', 'BranchController@download_pdf_branch');

    Route::get('/download-pending-order-pdf-branch/{dealer_id}', 'BranchController@download_pending_order_pdf_branch');
        
    Route::post(
        '/assign_dealer_to_branch',
        'BranchController@assign_dealer_to_branch'
    );
    Route::get(
        '/search-product-by-category/{category}',
        'BranchController@search_category'
    );
    Route::get(
        '/fetch_dealer_by_branch/{branch_id}',
        'BranchController@fetch_dealer_by_branch'
    );
    Route::get('/deactivate_branch/{id}', 'BranchController@deactivate_branch');
    Route::get('/restore_branch/{id}', 'BranchController@restore_branch');
    Route::get(
        '/branch_dashboard/{branch_id}',
        'BranchController@branch_dashboard'
    );
    Route::post(
        '/remove_dealer_from_branch',
        'BranchController@remove_dealer_from_branch'
    );
    Route::get(
        '/fetch_dealer_active_order/{dealer_id}',
        'BranchController@fetch_dealer_active_order'
    );
    Route::get(
        '/fetch_dealer_service_parts_order/{dealer_id}',
        'BranchController@fetch_dealer_service_parts_order'
    );
    Route::get(
        '/fetch_dealer_catalogue_order/{dealer_id}',
        'BranchController@fetch_dealer_catalogue_order'
    );
    Route::get(
        '/fetch_dealer_carded_products_order/{dealer_id}',
        'BranchController@fetch_dealer_carded_products_order'
    );

    Route::get(
        '/fetch_all_dealers_with_active_catalogue_order/{branch_id}',
        'BranchController@fetch_all_dealers_with_active_catalogue_order'
    );
    Route::get(
        '/fetch_all_dealers_with_active_order/{branch_id}',
        'BranchController@fetch_all_dealers_with_active_order'
    );

    Route::get(
        '/fetch_all_dealers_with_pending_order/{branch_id}',
        'BranchController@fetch_all_dealers_with_pending_order'
    );

    Route::get(
        '/fetch_dealer_with_pending_order/{dealer_id}',
        'BranchController@fetch_dealer_with_pending_order'
    );

    Route::get(
        '/fetch_all_dealers_with_active_carded_products_order/{branch_id}',
        'BranchController@fetch_all_dealers_with_active_carded_products_order'
    );
    Route::get(
        '/fetch_all_dealers_with_active_service_parts_order/{branch_id}',
        'BranchController@fetch_all_dealers_with_active_service_parts_order'
    );

    Route::get(
        '/fetch_dealers_by_id/{dealer_id}',
        'BranchController@fetch_dealers_by_id'
    );

    // Get all loggedin dealers for a branch
    Route::get(
        '/get-all-branch-loggedin-dealers/{branch_id}',
        'BranchController@get_all_branch_loggedin_dealers'
    );

    // Get all not loggedin dealers for a branch
    Route::get(
        '/get-all-branch-notloggedin-dealers/{branch_id}',
        'BranchController@get_all_branch_notloggedin_dealers'
    );

    // dealer order summary for sales rep
    Route::get(
        '/salesrep/get-dealer-order-summary/{id}',
        'BranchController@get_dealer_order_summary'
    );

    // get all the loggedin and not logged in dealers

    Route::get(
        '/get-loggedin-and-not-loggedin-dealers/{branch_id}',
        'BranchController@loggedin_and_not_loggedin_dealers'
    );

    // test for attaching image url to all products individually

    Route::get(
        '/attach_img_url_to_products',
        'DealerController@attach_img_url_to_products'
    );
});
