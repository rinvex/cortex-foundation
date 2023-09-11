<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\FrameGuard;
use Illuminate\Http\Middleware\HandleCors;
use Cortex\Foundation\Http\Middleware\TrimWww;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Session\Middleware\StartSession;
use Cortex\Foundation\Http\Middleware\Clockwork;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Cortex\Foundation\Http\Middleware\TrustHosts;
use Cortex\Foundation\Http\Middleware\TrimStrings;
use Cortex\Foundation\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Cortex\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Cortex\Foundation\Http\Middleware\SetNoCacheHeaders;
use Cortex\Foundation\Http\Middleware\EnforceTrailingSlash;
use Cortex\Foundation\Http\Middleware\LocalizationRedirect;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Cortex\Foundation\Http\Middleware\UnbindRouteParameters;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Cortex\Foundation\Http\Middleware\NotificationMiddleware;
use Cortex\Foundation\Http\Middleware\SetSessionConfigRuntime;
use Cortex\Foundation\Http\Middleware\DiscoverNavigationRoutes;
use Cortex\Foundation\Http\Middleware\SetCrawlingRobotsHeaders;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Cortex\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;

return function () {
    // Bind route models and constrains
    Route::pattern('locale', '[a-z]{2}');
    Route::pattern('media', '[a-zA-Z0-9-_]+');
    Route::pattern('accessarea', '[a-zA-Z0-9-_]+');
    Route::model('media', config('media-library.media_model'));
    Route::model('accessarea', config('cortex.foundation.models.accessarea'));

    Route::pattern('absentarea', '^([a-zA-Z0-9\-\.]+)$');
    Route::pattern('centralarea', route_pattern());
    Route::pattern('frontarea', route_pattern('frontarea'));
    Route::pattern('adminarea', route_pattern('adminarea'));

    // prepend middleware to the 'web' middleware group
    if(Route::hasMiddlewareGroup('web')){
        Route::prependMiddlewareToGroup('web', SetSessionConfigRuntime::class);
    }else{
        Route::middlewareGroup('web', [SetSessionConfigRuntime::class]);
    }

    // Map relations
    Relation::morphMap([
        'media' => config('media-library.media_model'),
        'accessarea' => config('cortex.foundation.models.accessarea'),
        'activity_model' => config('activitylog.activity_model'),
    ]);

    // Update the priority-sorted list of middleware. This forces non-global middleware to always be in the GIVEN ORDER.
    $this->app[Kernel::class]->appendToMiddlewarePriority(DiscoverNavigationRoutes::class);

    // Push middleware to the application's global HTTP middleware stack. These middleware are executed in every request in the GIVEN ORDER.
    $this->app[Kernel::class]->pushMiddleware(TrustHosts::class);
    $this->app[Kernel::class]->pushMiddleware(TrustProxies::class);
    $this->app[Kernel::class]->pushMiddleware(HandleCors::class);
    $this->app[Kernel::class]->pushMiddleware(PreventRequestsDuringMaintenance::class);
    $this->app[Kernel::class]->pushMiddleware(TrimWww::class);
    $this->app[Kernel::class]->pushMiddleware(EnforceTrailingSlash::class);
    $this->app[Kernel::class]->pushMiddleware(ValidatePostSize::class);
    $this->app[Kernel::class]->pushMiddleware(TrimStrings::class);
    $this->app[Kernel::class]->pushMiddleware(ConvertEmptyStringsToNull::class);
    $this->app[Kernel::class]->pushMiddleware(SetCrawlingRobotsHeaders::class);

    // Register application's route middleware.
    Route::aliasMiddleware('signed', ValidateSignature::class);
    Route::aliasMiddleware('throttle', ThrottleRequests::class);
    Route::aliasMiddleware('bindings', SubstituteBindings::class);
    Route::aliasMiddleware('cache.headers', SetCacheHeaders::class);
    Route::aliasMiddleware('nohttpcache', SetNoCacheHeaders::class);

    // Push middleware to route group. These middleware are executed in every request in the GIVEN ORDER.
    Route::pushMiddlewareToGroup('web', EncryptCookies::class);
    Route::pushMiddlewareToGroup('web', AddQueuedCookiesToResponse::class);
    Route::pushMiddlewareToGroup('web', StartSession::class);
    Route::pushMiddlewareToGroup('web', FrameGuard::class);
    Route::pushMiddlewareToGroup('web', LocalizationRedirect::class);
    Route::pushMiddlewareToGroup('web', ShareErrorsFromSession::class);
    Route::pushMiddlewareToGroup('web', VerifyCsrfToken::class);
    Route::pushMiddlewareToGroup('web', SubstituteBindings::class);
    Route::pushMiddlewareToGroup('web', NotificationMiddleware::class);
    Route::pushMiddlewareToGroup('web', DiscoverNavigationRoutes::class);
    Route::pushMiddlewareToGroup('web', UnbindRouteParameters::class);
    $this->app->environment('production') || Route::pushMiddlewareToGroup('web', Clockwork::class);
};
