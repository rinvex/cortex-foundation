<?php

declare(strict_types=1);

namespace Cortex\Foundation\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Rinvex\Fort\Exceptions\GenericException;
use Illuminate\Session\TokenMismatchException;
use Rinvex\Fort\Exceptions\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Watson\Validating\ValidationException as WatsonValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            return intend([
                'back' => true,
                'with' => ['warning' => trans('cortex/foundation::messages.token_mismatch')],
            ], 419);
        } elseif ($exception instanceof WatsonValidationException) {
            return intend([
                'back' => true,
                'withInput' => $request->all(),
                'withErrors' => $exception->errors(),
            ], 400);
        } elseif ($exception instanceof GenericException) {
            return intend([
                'url' => $exception->getRedirection() ?? route('frontend.home'),
                'withInput' => $exception->getInputs() ?? $request->all(),
                'with' => ['warning' => $exception->getMessage()],
            ], 422);
        } elseif ($exception instanceof AuthorizationException) {
            return intend([
                'url' => '/',
                'with' => ['warning' => $exception->getMessage()],
            ], 403);
        } elseif ($exception instanceof ModelNotFoundException) {
            $isBackend = mb_strpos($request->route()->getName(), 'backend') !== false;
            $single = mb_strtolower(mb_substr($exception->getModel(), mb_strrpos($exception->getModel(), '\\') + 1));
            $plural = str_plural($single);

            return intend([
                'url' => $isBackend ? route("backend.{$plural}.index") : route('frontend.home'),
                'with' => ['warning' => trans('cortex/foundation::messages.resource_not_found', ['resource' => $single, 'id' => $request->route()->parameter($single)])],
            ], 404);
        }

        return parent::render($request, $exception);
    }

    /**
     * Render the given HttpException.
     *
     * @param \Symfony\Component\HttpKernel\Exception\HttpException $e
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderHttpException(HttpException $e)
    {
        $status = $e->getStatusCode();

        if (view()->exists("cortex/foundation::common.errors.{$status}")) {
            return response()->view("cortex/foundation::common.errors.{$status}", ['exception' => $e], $status, $e->getHeaders());
        } else {
            return parent::renderHttpException($e);
        }
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param \Illuminate\Http\Request                 $request
     * @param \Illuminate\Auth\AuthenticationException $exception
     *
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return intend([
            'url' => route('frontend.auth.login'),
            'with' => ['warning' => trans('cortex/foundation::messages.session_required')],
        ], 401);
    }
}
