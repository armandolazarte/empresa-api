<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function() {

    // CommonLaravel 
    // ----------------------------------------------------------------------------------------------------
    // Generals
    Route::post('search/{model_name}', 'CommonLaravel\SearchController@search');
    Route::post('search/save-if-not-exist/{model_name}/{propertye}/{query}', 'CommonLaravel\SearchController@saveIfNotExist');
    Route::get('previus-day/{model_name}/{index}', 'CommonLaravel\PreviusDayController@previusDays');
    Route::get('previus-next/{model_name}/{index}', 'CommonLaravel\PreviusNextController@previusNext');
    Route::get('previus-next-index/{model_name}/{id}', 'CommonLaravel\PreviusNextController@getIndexPreviusNext');
    Route::put('update/{model_name}', 'CommonLaravel\UpdateController@update');
    Route::put('delete/{model_name}', 'CommonLaravel\DeleteController@delete');
    
    // User
    Route::get('user', 'CommonLaravel\AuthController@user');
    Route::put('user/{id}', 'UserController@update');
    Route::put('user-password', 'CommonLaravel\UserController@updatePassword');

    // Employee
    Route::resource('employee', 'CommonLaravel\EmployeeController');

    // Permissions
    Route::get('permission', 'CommonLaravel\PermissionController@index');

    // Images
    Route::post('set-image/{prop}', 'CommonLaravel\ImageController@setImage');
    Route::delete('delete-image-prop/{model_name}/{id}/{prop_name}', 'CommonLaravel\ImageController@deleteImageProp');
    Route::delete('delete-image-model/{model_name}/{model_id}/{image_id}', 'CommonLaravel\ImageController@deleteImageModel');

    // ----------------------------------------------------------------------------------------------------

    Route::resource('configuration', 'UserConfigurationController');
    Route::post('set-comercio-city-user', 'GeneralController@setComercioCityUser');

    Route::resource('article', 'ArticleController')->except(['index']);
    Route::get('article/index/from-status/{last_updated}/{status?}', 'ArticleController@index');
    Route::get('/article/deleted-models/{last_updated}', 'ArticleController@deletedModels');
    Route::post('/article/excel/import', 'ArticleController@import');
    Route::post('/article/new-article', 'ArticleController@newArticle');
    Route::get('/article/set-featured/{id}', 'ArticleController@setFeatured');
    Route::get('/article/set-online/{id}', 'ArticleController@setOnline');
    Route::get('/article/charts/{id}/{from_date}/{until_date}', 'ArticleController@charts');

    Route::resource('sale', 'SaleController');
    Route::get('sale/from-date/{from_date}/{until_date?}', 'SaleController@index');
    Route::put('sale/update-prices/{id}', 'SaleController@updatePrices');
    Route::get('sale/charts/{from}/{to}', 'SaleController@charts');

    Route::resource('brand', 'BrandController');
    Route::resource('category', 'CategoryController');
    Route::resource('condition', 'ConditionController');
    Route::resource('iva', 'IvaController');
    Route::resource('provider', 'ProviderController');
    Route::resource('provider-price-list', 'ProviderPriceListController');
    Route::resource('sub-category', 'SubCategoryController');
    Route::resource('iva-condition', 'IvaConditionController');
    Route::resource('location', 'LocationController');
    Route::resource('current-acount-payment-method', 'CurrentAcountPaymentMethodController');
    Route::resource('client', 'ClientController');
    Route::post('client/excel/import', 'ClientController@import');
    Route::resource('seller', 'SellerController');
    Route::resource('price-type', 'PriceTypeController');
    Route::resource('provider-order', 'ProviderOrderController');
    Route::get('provider-order/from-date/{from_date}/{until_date?}', 'ProviderOrderController@index');
    Route::resource('provider-order-status', 'ProviderOrderStatusController');
    Route::resource('provider-order-afip-ticket', 'ProviderOrderAfipTicketController');
    
    Route::resource('order', 'OrderController')->except(['index']);
    Route::get('order/from-date/{from_date}/{until_date?}', 'OrderController@index');
    Route::put('order/update-status/{order_id}', 'OrderController@updateStatus');
    Route::put('order/cancel/{order_id}', 'OrderController@cancel');

    Route::resource('order-status', 'OrderStatusController');
    Route::resource('buyer', 'BuyerController');
    Route::resource('delivery-zone', 'DeliveryZoneController');

    Route::resource('payment-method', 'PaymentMethodController');

    Route::resource('payment-method-type', 'PaymentMethodTypeController');

    Route::resource('deposit', 'DepositController');
    Route::resource('size', 'SizeController');
    Route::resource('color', 'ColorController');
    Route::resource('article-discount', 'ArticleDiscountController');
    Route::resource('description', 'DescriptionController');
    Route::resource('discount', 'DiscountController');
    Route::resource('surchage', 'SurchageController');
    Route::post('service', 'ServiceController@store');
    Route::resource('budget', 'BudgetController')->except(['index']);
    Route::get('budget/from-date/{from_date}/{until_date?}', 'BudgetController@index');
    Route::resource('budget-status', 'BudgetStatusController');
    Route::resource('afip-information', 'AfipInformationController');

    Route::resource('production-movement', 'ProductionMovementController')->except(['index']);
    Route::get('production-movement/from-date/{from_date}/{until_date?}', 'ProductionMovementController@index');
    Route::get('production-movement/current-amounts/{article_id}', 'ProductionMovementController@currentAmounts');

    Route::resource('order-production', 'OrderProductionController');
    Route::resource('order-production-status', 'OrderProductionStatusController');
    Route::resource('recipe', 'RecipeController');
    Route::resource('address', 'AddressController');

    Route::resource('title', 'TitleController');

    Route::get('message/{buyer_id}', 'MessageController@fromBuyer');
    Route::get('message/set-read/{buyer_id}', 'MessageController@setRead');
    Route::post('message', 'MessageController@store');

    Route::resource('-', '-Controller');

    // CurrentAcounts
    Route::get('/current-acount/{model_name}/{model_id}/{months_ago}', 'CurrentAcountController@index');
    Route::post('/current-acount/pago', 'CurrentAcountController@pago');
    Route::post('/current-acount/nota-credito', 'CurrentAcountController@notaCredito');
    Route::post('/current-acount/nota-debito', 'CurrentAcountController@notaDebito');
    Route::post('/current-acount/saldo-inicial', 'CurrentAcountController@saldoInicial');
    Route::delete('/current-acount/{model_name}/{id}', 'CurrentAcountController@delete');

    Route::get('/import-history/{model_name}', 'ImportHistoryController@index');

    Route::get('/online-price-type', 'OnlinePriceTypeController@index');

    Route::resource('/cupon', 'CuponController');

    Route::get('/mercado-pago/payment/{payment_id}', 'MercadoPagoController@payment');

    Route::get('report/from-date/{from_date}/{until_date?}', 'CajaController@reports');
    Route::get('chart/from-date/{from_date}/{until_date?}', 'CajaController@charts');

    Route::resource('commission', 'CommissionController');
    Route::get('seller-commission/{model_id}/{from_date}/{until_date}', 'SellerCommissionController@index');
    Route::post('seller-commission/saldo-inicial', 'SellerCommissionController@saldoInicial');
    Route::post('seller-commission/pago', 'SellerCommissionController@pago');
    Route::delete('seller-commission/{id}', 'SellerCommissionController@destroy');

    Route::resource('sale-type', 'SaleTypeController');

    Route::get('pagado-por/{model_name}/{model_id}/{debe_id}/{haber_id}', 'PagadoPorController@index');
});
