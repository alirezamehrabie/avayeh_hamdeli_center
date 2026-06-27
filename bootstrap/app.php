<?php

use App\Services\LoginRedirector;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES'),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $exception, $request) {
            if (! auth()->check() || $request->expectsJson()) {
                return null;
            }

            $redirector = app(LoginRedirector::class);
            $user = auth()->user();

            if (! $redirector->hasAuthorizedPanel($user)) {
                return null;
            }

            return redirect()
                ->to($redirector->pathFor($user))
                ->with('error', 'شما به این بخش دسترسی ندارید و به پنل مجاز خود هدایت شدید.');
        });
    })->create();
