<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Services\AI\AiException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind the nginx container (and an upstream TLS terminator / Cloudflare),
        // trust the forwarded proto/host so HTTPS is detected. Without this, signed
        // magic-login links, payment-return URLs and secure cookies break in prod.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Gateways/rails post server-to-server with no CSRF token; verified by signature.
        $middleware->validateCsrfTokens(except: ['paypal/webhook', 'stripe/webhook']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // AI provider failures always answer JSON on their fetch endpoints, with
        // a status the frontend can act on: 429 = rate-limited (wait), else 502.
        $exceptions->render(function (AiException $e) {
            $rateLimited = $e->status === 429;

            return response()->json([
                'message' => $rateLimited
                    ? 'The AI assistant is rate-limited right now. Please wait a minute and try again.'
                    : 'The AI assistant is temporarily unavailable. Please try again shortly.',
            ], $rateLimited ? 429 : 502);
        });
    })->create();
