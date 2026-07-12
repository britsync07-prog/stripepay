<?php

namespace App\Providers;

use App\Payments\PaymentManager;
use App\Payments\PayoutRailManager;
use App\Services\AI\AiProvider;
use App\Services\AI\Gemini;
use App\Services\AI\Groq;
use App\Services\AI\OpenAi;
use App\Services\Payment\StripeCheckout;
use App\Services\Payment\StripeConnect;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class, fn ($app) => new PaymentManager($app));
        $this->app->singleton(PayoutRailManager::class, fn ($app) => new PayoutRailManager($app));
        $this->app->bind(StripeCheckout::class, fn () => StripeCheckout::make());
        $this->app->bind(StripeConnect::class, fn () => StripeConnect::make());

        // Resolve the active AI provider from config — switching Groq ↔ OpenAI ↔
        // Gemini is a one-line AI_PROVIDER change. Bound (not singleton) so it
        // re-reads config each resolve.
        $this->app->bind(AiProvider::class, fn () => match (config('services.ai.default')) {
            'openai' => new OpenAi,
            'gemini' => new Gemini,
            default => new Groq,
        });

        // Ignore unknown ?filter[...] params instead of throwing a 500 (lists only
        // allow a subset of filters; a stray param shouldn't break the page).
        config(['query-builder.disable_invalid_filter_query_exception' => true]);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('payment-session', function (Request $request) {
            $consultation = $request->route('consultationRequest');
            $uuid = is_object($consultation) ? (string) $consultation->uuid : (string) $consultation;
            $amount = is_object($consultation) ? (int) $consultation->amount : null;
            $gateway = (string) $request->route('gateway', 'unknown');
            $key = implode('|', [$request->ip(), $uuid, $gateway]);
            $response = function () use ($request, $uuid, $amount, $gateway) {
                Log::warning('[payment] checkout session rate limited', [
                    'consultation' => $uuid,
                    'gateway' => $gateway,
                    'amount' => $amount,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'message' => 'Too many payment attempts. Please wait and try again.',
                ], 429);
            };

            return [
                Limit::perMinute(5)->by($key)->response($response),
                Limit::perDay(20)->by($key)->response($response),
            ];
        });
    }
}
