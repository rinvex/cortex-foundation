<?php

declare(strict_types=1);

namespace Cortex\Foundation\Providers;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Cortex\Foundation\Http\FormRequest;
use Illuminate\Support\ServiceProvider;
use Rinvex\Support\Traits\ConsoleTools;
use Cortex\Foundation\Models\Accessarea;
use Illuminate\Database\Schema\Blueprint;
use Cortex\Foundation\Validators\Validator;
use Illuminate\View\Compilers\BladeCompiler;
use Cortex\Foundation\Generators\LangJsGenerator;
use Illuminate\Support\Facades\Session as SessionFacade;
use Cortex\Foundation\Verifiers\EloquentPresenceVerifier;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Cortex\Foundation\Http\Middleware\NotificationMiddleware;
use Cortex\Foundation\Overrides\Illuminate\Routing\Redirector;
use Cortex\Foundation\Overrides\Barryvdh\Debugbar\DebugbarServiceProvider;
use Cortex\Foundation\Overrides\Mcamara\LaravelLocalization\LaravelLocalization;
use Cortex\Foundation\Overrides\Mariuzzo\LaravelJsLocalization\Commands\LangJsCommand;
use Cortex\Foundation\Overrides\Felixkiss\UniqueWithValidator\Validator as UniqueWithValidator;

class FoundationServiceProvider extends ServiceProvider
{
    use ConsoleTools;

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
        $this->overrideNotificationMiddleware();
        $this->overrideLaravelLocalization();
        $this->bindPresenceVerifier();
        $this->bindBlueprintMacro();
        $this->bindBladeCompiler();
        $this->overrideLangJS();

        // Bind eloquent models to IoC container
        $this->registerModels([
            'cortex.foundation.accessarea' => Accessarea::class,
        ]);

        // Override datatables html builder
        $this->app->bind(\Yajra\DataTables\Html\Builder::class, \Cortex\Foundation\Overrides\Yajra\DataTables\Html\Builder::class);

        // Register dev service providers
        $this->app->environment('production') || $this->app->register(DebugbarServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Fix the specified key was too long error
        Schema::defaultStringLength(191);

        // Use Pagination bootstrap styles
        Paginator::useBootstrap();

        // Override validator resolver
        ValidatorFacade::resolver(function ($translator, $data, $rules, $messages) {
            return new Validator($translator, $data, $rules, $messages);
        });

        // Override validator presence verifier
        if (isset($this->app['db'], $this->app['cortex.foundation.presence.verifier'])) {
            $this->app['validator']->setPresenceVerifier($this->app['cortex.foundation.presence.verifier']);
        }

        // Early set application locale globaly
        $this->app['laravellocalization']->setLocale();

        $this->overrideUniqueWithValidator();

        SessionFacade::extend('database', function ($app) {
            $table = $app['config']['session.table'];

            $lifetime = $app['config']['session.lifetime'];
            $connection = $app['config']['session.connection'];

            return new \Cortex\Foundation\Overrides\Illuminate\Session\DatabaseSessionHandler(
                $app['db']->connection($connection),
                $table,
                $lifetime,
                $app
            );
        });

        /**
         * Get an item from the patterns by key.
         *
         * @param mixed $key
         * @param mixed $default
         *
         * @return mixed
         */
        Router::macro('getPattern', function ($key, $default = null) {
            if (array_key_exists($key, $this->patterns)) {
                return $this->patterns[$key];
            }

            return value($default);
        });

        /**
         * Determine a given parameter name exists from the route.
         *
         * @param string $name
         *
         * @return bool
         */
        Route::macro('hasParameterName', function ($name) {
            if ($parameterNames = $this->parameterNames()) {
                return in_array($name, $parameterNames);
            }

            return false;
        });

        // Override `FormRequest` container binding
        $this->app->resolving(FormRequest::class, function ($request, $app) {
            $request = FormRequest::createFrom($app['request'], $request);

            $request->setContainer($app)->setRedirector($app->make(Redirector::class));
        });
    }
    /**
     * Override notification middleware.
     *
     * @return void
     */
    public function overrideUniqueWithValidator(): void
    {
        if (method_exists($this->app->translator, 'trans')) {
            $message = $this->app->translator->trans('uniquewith-validator::validation.unique_with');
        }
        else {
            $message = $this->app->translator->get('uniquewith-validator::validation.unique_with');
        }
        $this->app->validator->extend('unique_with', UniqueWithValidator::class . '@validateUniqueWith', $message);
        $this->app->validator->replacer('unique_with', function() {
            // Since 5.4.20, the validator is passed in as the 5th parameter.
            // In order to preserve backwards compatibility, we check if the 
            // validator is passed and use the validator's translator instead
            // of getting it out of the container.
            $arguments = func_get_args();
            if (sizeof($arguments) >= 5) {
                $arguments[4] = $arguments[4]->getTranslator();
            }
            else {
                $arguments[4] = $this->app->translator;
            }

            return call_user_func_array([new UniqueWithValidator, 'replaceUniqueWith'], $arguments);
        });
    }
    /**
     * Override notification middleware.
     *
     * @return void
     */
    protected function overrideNotificationMiddleware(): void
    {
        $this->app->singleton('Cortex\Foundation\Http\Middleware\NotificationMiddleware', function ($app) {
            return new NotificationMiddleware(
                $app['session.store'],
                $app['notification'],
                $app['config']->get('notification.session_key')
            );
        });
    }

    /**
     * Bind blade compiler.
     *
     * @return void
     */
    protected function bindBladeCompiler(): void
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {

            // @alerts('container')
            $bladeCompiler->directive('alerts', function ($container = null) {
                if (strcasecmp('()', $container) === 0) {
                    $container = null;
                }

                return "<?php echo app('notification')->container($container)->show(); ?>";
            });
        });
    }

    /**
     * Override the LaravelLocalization instance.
     *
     * @return void
     */
    protected function overrideLaravelLocalization(): void
    {
        $this->app->singleton('laravellocalization', function () {
            return new LaravelLocalization();
        });
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    protected function overrideLangJS(): void
    {
        // Bind the Laravel JS Localization command into the app IOC.
        $this->app->singleton('localization.js', function () {
            $files = $this->app['files'];
            $messages = $this->app['config']->get('localization-js.messages');
            $generator = new LangJsGenerator($files, $this->app['path.base'].'/resources/lang', $messages);

            return new LangJsCommand($generator);
        });

        // Bind the Laravel JS Localization command into Laravel Artisan.
        $this->commands('localization.js');
    }

    /**
     * Bind presence verifier.
     *
     * @return void
     */
    protected function bindPresenceVerifier(): void
    {
        $this->app->bind('cortex.foundation.presence.verifier', function ($app) {
            return new EloquentPresenceVerifier($app['db']);
        });
    }

    /**
     * Bind blueprint macro.
     *
     * @return void
     */
    protected function bindBlueprintMacro(): void
    {
        Blueprint::macro('auditable', function () {
            $this->integer('created_by_id')->unsigned()->after('created_at')->nullable();
            $this->string('created_by_type')->after('created_at')->nullable();
            $this->integer('updated_by_id')->unsigned()->after('updated_at')->nullable();
            $this->string('updated_by_type')->after('updated_at')->nullable();
        });

        Blueprint::macro('dropAuditable', function () {
            $this->dropForeign($this->createIndexName('foreign', ['updated_by_type']));
            $this->dropForeign($this->createIndexName('foreign', ['updated_by_id']));
            $this->dropForeign($this->createIndexName('foreign', ['created_by_type']));
            $this->dropForeign($this->createIndexName('foreign', ['created_by_id']));
            $this->dropColumn(['updated_by_type', 'updated_by_id', 'created_by_type', 'created_by_id']);
        });

        Blueprint::macro('auditableAndTimestamps', function ($precision = 0) {
            $this->timestamp('created_at', $precision)->nullable();
            $this->integer('created_by_id')->unsigned()->nullable();
            $this->string('created_by_type')->nullable();
            $this->timestamp('updated_at', $precision)->nullable();
            $this->integer('updated_by_id')->unsigned()->nullable();
            $this->string('updated_by_type')->nullable();
        });

        Blueprint::macro('dropauditableAndTimestamps', function () {
            $this->dropForeign($this->createIndexName('foreign', ['updated_by_type']));
            $this->dropForeign($this->createIndexName('foreign', ['updated_by_id']));
            $this->dropForeign($this->createIndexName('foreign', ['updated_at']));
            $this->dropForeign($this->createIndexName('foreign', ['created_by_type']));
            $this->dropForeign($this->createIndexName('foreign', ['created_by_id']));
            $this->dropForeign($this->createIndexName('foreign', ['created_at']));
            $this->dropColumn(['updated_by_type', 'updated_by_id', 'updated_at', 'created_by_type', 'created_by_id', 'created_at']);
        });
    }
}
