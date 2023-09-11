<?php

declare(strict_types=1);

namespace Cortex\Foundation\Exceptions;

use Exception;
use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\Route;
use Rinvex\Country\CountryLoaderException;
use Illuminate\Auth\AuthenticationException;
use Rinvex\Language\LanguageLoaderException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Rinvex\University\UniversityLoaderException;
use Illuminate\Auth\Access\AuthorizationException;
use Rinvex\Tenants\Exceptions\AbstractTenantException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Foundation\Exceptions\Handler as BaseExceptionHandler;
use Watson\Validating\ValidationException as WatsonValidationException;

class ExceptionHandler extends BaseExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        GenericException::class,
        CountryLoaderException::class,
        LanguageLoaderException::class,
        UniversityLoaderException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Throwable $e
     *
     * @throws \Exception
     *
     * @return void
     */
    public function report(Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable               $e
     *
     * @throws \Throwable
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            return intend([
                'back' => true,
                'with' => ['warning' => trans('cortex/foundation::messages.token_mismatch')],
            ], 403);
        } elseif ($e instanceof WatsonValidationException) {
            return intend([
                'intended' => $e->redirectTo ?? url()->previous(),
                'withInput' => $request->all(),
                'with' => ['warning' => $e->getMessage()],
                'withErrors' => $e->errors(),
            ], $e->status); // 422
        } elseif ($e instanceof ValidationException) {
            return intend([
                'intended' => $e->redirectTo ?? url()->previous(),
                'withInput' => $request->all(),
                'with' => ['warning' => $e->getMessage()],
                'withErrors' => $e->errors(),
            ], $e->status); // 422
        } elseif ($e instanceof GenericException) {
            return intend([
                'url' => $e->getRedirection() ?? route("{$request->accessarea()}.home"),
                'withInput' => $e->getInputs() ?? $request->all(),
                'with' => ['warning' => $e->getMessage()],
            ], $e->getStatusCode()); // 401, 403, 302
        } elseif ($e instanceof AuthenticationException) {
            // Save state, and redirect, or resubmit form after authentication
            request()->expectsJson() || redirect()->saveStateUntilAuthentication();

            return intend([
                'url' => route($request->accessarea().'.cortex.auth.account.login'),
                'with' => ['warning' => trans('cortex/auth::messages.unauthenticated')],
            ], 401);
        } elseif ($e instanceof AuthorizationException) {
            return intend([
                'url' => in_array($request->accessarea(), ['tenantarea', 'managerarea']) ? route('tenantarea.home') : route('frontarea.home'),
                'with' => ['warning' => $e->getMessage()],
            ], 403);
        } elseif ($e instanceof NotFoundHttpException) {
            // Catch localized routes with missing {locale}
            // and redirect them to the correct localized version
            if (config('cortex.foundation.route.locale_redirect')) {
                $originalUrl = $request->url();

                try {
                    $localizedUrl = app('laravellocalization')->getLocalizedURL(null, $originalUrl);

                    // Will return `NotFoundHttpException` exception if no match found!
                    app('router')->getRoutes()->match($request->create($localizedUrl));

                    return intend([
                        'url' => $originalUrl !== $localizedUrl ? $localizedUrl : route("{$request->accessarea()}.home"),
                        'with' => ['warning' => $e->getMessage()],
                    ], $e->getStatusCode()); // 404
                } catch (Exception $e) {
                }
            }

            return $this->prepareResponse($request, $e);
        } elseif ($e instanceof ModelNotFoundException) {
            $model = Str::lower($e->getModel());
            $vendor = Str::before($model, '\\');
            $resource = Str::afterLast($model, '\\');
            $route = $request->accessarea().'.'.$vendor.'.'.Str::plural($resource).'.'.Str::plural($resource).'.index';
            preg_match('/'.(Route::getPattern($resource) ?: '[a-zA-Z0-9-_]+').'/', $request->route($resource), $matches);

            return intend([
                'url' => Route::has($route) ? route($route) : route("{$request->accessarea()}.home"),
                'with' => ['warning' => trans('cortex/foundation::messages.resource_not_found', ['resource' => $resource, 'identifier' => $matches[0]])],
            ], 404);
        } elseif ($e instanceof UniversityLoaderException || $e instanceof CountryLoaderException || $e instanceof LanguageLoaderException) {
            return intend([
                'url' => route("{$request->accessarea()}.home"),
                'with' => ['warning' => $e->getMessage()],
            ], 404);
        } elseif ($e instanceof ThrottleRequestsException) {
            return intend([
                'back' => true,
                'withInput' => $request->all(),
                'with' => ['warning' => $e->getMessage()],
            ], $e->getStatusCode()); // 429
        } elseif ($e instanceof AbstractTenantException) {
            return intend([
                'url' => route('frontarea.home'),
                'with' => ['warning' => $e->getMessage()],
            ], 404); // 429
        }

        return parent::render($request, $e);
    }

    /**
     * Render the given HttpException.
     *
     * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderHttpException(HttpExceptionInterface $e)
    {
        if (view()->exists($view = $this->getHttpExceptionView($e))) {
            return response()->view($view, ['errors' => new ViewErrorBag(), 'exception' => $e], $e->getStatusCode(), $e->getHeaders());
        }

        return parent::renderHttpException($e);
    }

    /**
     * Get the view used to render HTTP exceptions.
     *
     * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e
     *
     * @return string
     */
    protected function getHttpExceptionView(HttpExceptionInterface $e)
    {
        $accessarea = request()->accessarea();
        switch ($accessarea) {
            case 'managerarea':
            case 'tenantarea':
                $view = "cortex/tenants::{$accessarea}.errors.{$e->getStatusCode()}";
                break;
            default:
                $view ="cortex/foundation::{$accessarea}.errors.{$e->getStatusCode()}";
                break;
        }
        return $view;
    }
}
