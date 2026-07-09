<?php

namespace App\Http\Middleware;

use App\Data\Notification\NotificationData;
use App\Models\User;
use App\Payments\PaymentManager;
use App\Services\AI\Ai;
use App\Services\SettingsService;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'isAdmin' => (bool) $request->user()?->hasRole('admin'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'notifications' => $this->notificationsFor($request->user()),
            'gatewayRegistry' => $this->gatewayRegistry(),
            'currency' => [
                'code' => SettingsService::currencyCode(),
                'symbol' => SettingsService::currencySymbol(),
            ],
            'company' => SettingsService::company(),
            'uploads' => [
                'accept' => Uploads::acceptMaps(),
            ],
            // Whether AI assistance is configured — admin AI surfaces gate on this.
            'aiEnabled' => Ai::configured(),
        ];
    }

    /**
     * Payment gateway keys + render modes, sourced from the backend so the
     * frontend never hardcodes them. Shared globally for any checkout surface.
     *
     * @return array{keys: array<string, string>, modes: array<string, string>}
     */
    private function gatewayRegistry(): array
    {
        $payments = app(PaymentManager::class);

        return [
            'keys' => $payments->keys(),
            'modes' => $payments->modes(),
        ];
    }

    private function notificationsFor(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'items' => NotificationData::collect(
                $user->notifications()->latest()->limit(8)->get()
            ),
            'unread' => $user->unreadNotifications()->count(),
        ];
    }
}
