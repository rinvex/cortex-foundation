<?php

declare(strict_types=1);

Route::domain(domain())->group(function () {
    Route::name('adminarea.')
         ->namespace('Cortex\Foundation\Http\Controllers\Adminarea')
         ->middleware(['web', 'nohttpcache', 'can:access-adminarea'])
         ->prefix(config('cortex.foundation.route.locale_prefix') ? '{locale}/'.config('cortex.foundation.route.prefix.adminarea') : config('cortex.foundation.route.prefix.adminarea'))->group(function () {

            // Adminarea Home route
             Route::get('/')->name('home')->uses('HomeController@index');
         });

    Route::name('memberarea.')
         ->middleware(['web', 'nohttpcache'])
         ->namespace('Cortex\Foundation\Http\Controllers\Memberarea')
         ->prefix(config('cortex.foundation.route.locale_prefix') ? '{locale}/'.config('cortex.foundation.route.prefix.memberarea') : config('cortex.foundation.route.prefix.memberarea'))->group(function () {

            // Homepage Routes
             Route::get('/')->name('home')->uses('HomeController@index');
         });
});

Route::domain('{subdomain}.'.domain())->group(function () {
    Route::name('tenantarea.')
         ->namespace('Cortex\Foundation\Http\Controllers\Tenantarea')
         ->middleware(['web', 'nohttpcache', 'can:access-tenantarea'])
         ->prefix(config('cortex.foundation.route.locale_prefix') ? '{locale}/'.config('cortex.foundation.route.prefix.tenantarea') : config('cortex.foundation.route.prefix.tenantarea'))->group(function () {

            // Tenantarea Home route
             Route::get('/')->name('home')->uses('HomeController@index');
         });
});
