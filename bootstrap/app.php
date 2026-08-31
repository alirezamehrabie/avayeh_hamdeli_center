<?php

use App\Services\LoginRedirector;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\AddSecurityHeaders::class);

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

            $redirectPath = $redirector->pathFor($user);

            Log::warning('auth.authorization.safe_panel_redirect', [
                'user_id' => $user->id,
                'access_level' => $user->access_level,
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'redirect_url' => $redirectPath,
                'exception' => $exception::class,
            ]);

            return redirect()
                ->to($redirectPath)
                ->with('error', 'شما به این بخش دسترسی ندارید و به پنل مجاز خود هدایت شدید.');
        });
    })->create();
