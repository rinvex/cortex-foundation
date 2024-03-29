<?php

declare(strict_types=1);

namespace Cortex\Foundation\Providers;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class BootServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register(): void
    {
        // Register collection loading prioritization macro
        Collection::macro(
            'prioritizeLoading',
            fn () => $this->partition(fn ($item) => Str::contains($item, config('app.provider_loading.priority_5')))->flatMap(fn ($values) => $values)
                 ->partition(fn ($item) => Str::contains($item, config('app.provider_loading.priority_4')))->flatMap(fn ($values) => $values)
                 ->partition(fn ($item) => Str::contains($item, config('app.provider_loading.priority_3')))->flatMap(fn ($values) => $values)
                 ->partition(fn ($item) => Str::contains($item, config('app.provider_loading.priority_2')))->flatMap(fn ($values) => $values)
                 ->partition(fn ($item) => Str::contains($item, config('app.provider_loading.priority_1')))->flatMap(fn ($values) => $values)
        );

        // Register modules list
        $this->app->singleton('cortex.foundation.modules', fn () => collect(is_file(config('rinvex.composer.cortex-module.manifest')) ? $this->app['files']->getRequire(config('rinvex.composer.cortex-module.manifest')) : []));
        $this->app->singleton('cortex.foundation.modules.enabled', fn () => $this->app['cortex.foundation.modules']->filter(fn ($module) => $module['active'] && $module['autoload']));
        $this->app->singleton('cortex.foundation.modules.enabled.paths', fn () => $this->app['cortex.foundation.modules.enabled']->map(fn ($val, $key) => app()->modulePath($key))->filter(fn ($path) => file_exists($path))->toArray());

        $this->app->singleton('cortex.foundation.extensions', fn () => collect(is_file(config('rinvex.composer.cortex-extension.manifest')) ? $this->app['files']->getRequire(config('rinvex.composer.cortex-extension.manifest')) : []));
        $this->app->singleton('cortex.foundation.extensions.enabled', fn () => $this->app['cortex.foundation.extensions']->filter(fn ($extension) => $extension['active'] && $extension['autoload'] && $this->app['cortex.foundation.modules.enabled']->has($extension['extends'] ?? null)));
        $this->app->singleton('cortex.foundation.extensions.enabled.paths', fn () => $this->app['cortex.foundation.extensions.enabled']->map(fn ($val, $key) => app()->extensionPath($key))->filter(fn ($path) => file_exists($path))->toArray());

        $enabledModulesPaths = $this->app['cortex.foundation.modules.enabled.paths'];
        $enabledExtensionsPaths = $this->app['cortex.foundation.extensions.enabled.paths'];

        // Register filesystem module resources macro
        Filesystem::macro('moduleResources', function ($resource, $type = 'files', $depth = 1) use ($enabledModulesPaths) {
            return $enabledModulesPaths ? iterator_to_array(Finder::create()->{$type}()->in($enabledModulesPaths)->path($resource)->depth($depth)->sortByName(), false) : [];
        });

        // Register filesystem extension resources macro
        Filesystem::macro('extensionResources', function ($resource, $type = 'files', $depth = 1) use ($enabledExtensionsPaths) {
            return $enabledExtensionsPaths ? iterator_to_array(Finder::create()->{$type}()->in($enabledExtensionsPaths)->path($resource)->depth($depth)->sortByName(), false) : [];
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        try {
            // Just check if we have DB connection! This is to avoid
            // exceptions on new projects before configuring database options
            // @TODO: refcator the whole accessareas retrieval to be file-based, instead of db based
            DB::connection()->getPdo();

            if (Schema::hasTable(config('cortex.foundation.tables.accessareas'))) {
                // Register accessareas into service container, early before booting any module service providers!
                $this->app->singleton('accessareas', fn () => app('cortex.foundation.accessarea')->where('is_active', true)->get());
            }
        } catch (Exception $e) {
            // Be quiet! Do not do or say anything!!
        }

        $this->bootstrapModules();
    }

    /**
     * Bootstrap application modules.
     *
     * @return void
     */
    public function bootstrapModules(): void
    {
        foreach (['module', 'extension'] as $moduleType) {
            $resources = $this->app['files']->{"{$moduleType}Resources"}('bootstrap/module.php');

            collect($resources)
                ->prioritizeLoading()
                ->each(fn ($file) => (require $file)());
        }
    }
}
