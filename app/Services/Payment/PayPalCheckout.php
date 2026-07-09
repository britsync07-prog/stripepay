<?php

namespace App\Services\Payment;

use App\Models\ConsultationRequest;
use Brick\Money\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the PayPal Orders v2 REST API — the PayPal counterpart
 * to {@see StripeCheckout}. Uses the Laravel HTTP client (no SDK dependency).
 */
class PayPalCheckout
{
    /** PayPal environment modes (config `services.paypal.mode`). */
    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    /** REST API base URL per environment. */
    private const BASE_URLS = [
        self::MODE_SANDBOX => 'https://api-m.sandbox.paypal.com',
        self::MODE_LIVE => 'https://api-m.paypal.com',
    ];

    /** Memoized OAuth token, reused across this instance's calls (e.g. verify()'s getOrder + captureOrder). */
    private ?string $token = null;

    public function __construct(
        private readonly string $clientId,
        private readonly string $secret,
        private readonly string $baseUrl,
    ) {}

    public static function make(): self
    {
        $mode = (string) config('services.paypal.mode', self::MODE_SANDBOX);

        return new self(
            (string) config('services.paypal.client_id'),
            (string) config('services.paypal.client_secret'),
            self::BASE_URLS[$mode] ?? self::BASE_URLS[self::MODE_SANDBOX],
        );
    }

    public static function configured(): bool
    {
        return filled(config('services.paypal.client_id'))
            && filled(config('services.paypal.client_secret'));
    }

    /** Decimal amount string PayPal expects (e.g. "120.00"), derived from minor units. */
    public static function decimalAmount(ConsultationRequest $request): string
    {
        return (string) Money::ofMinor($request->amount, $request->currency)->getAmount();
    }

    public function createOrder(ConsultationRequest $request, string $returnUrl, string $cancelUrl): array
    {
        $response = $this->client()->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'custom_id' => $request->uuid,
                'description' => $request->plan?->name ?? 'Consultation',
                'amount' => [
                    'currency_code' => strtoupper((string) $request->currency),
                    'value' => self::decimalAmount($request),
                ],
            ]],
            'application_context' => [
                'brand_name' => (string) config('app.name'),
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('PayPal create order failed: '.$response->body());
        }

        return $response->json();
    }

    /** Read an order without capturing — used to validate before taking any money. */
    public function getOrder(string $orderId): array
    {
        $response = $this->client()->get("/v2/checkout/orders/{$orderId}");

        if ($response->failed()) {
            throw new RuntimeException('PayPal get order failed: '.$response->body());
        }

        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        // PayPal's capture endpoint takes no parameters, but the JSON client must
        // still send a well-formed body — an empty object {} (not an empty array).
        $response = $this->client()->post("/v2/checkout/orders/{$orderId}/capture", (object) []);

        if ($response->failed()) {
            throw new RuntimeException('PayPal capture failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Verify a webhook's authenticity with PayPal (so events can't be spoofed).
     *
     * @param  array<string, string|null>  $headers  PayPal transmission headers
     * @param  array<string, mixed>  $event  the decoded webhook event body
     */
    public function verifyWebhook(array $headers, array $event): bool
    {
        $webhookId = (string) config('services.paypal.webhook_id');

        if ($webhookId === '') {
            return false;
        }

        $response = $this->client()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $headers['paypal-auth-algo'] ?? null,
            'cert_url' => $headers['paypal-cert-url'] ?? null,
            'transmission_id' => $headers['paypal-transmission-id'] ?? null,
            'transmission_sig' => $headers['paypal-transmission-sig'] ?? null,
            'transmission_time' => $headers['paypal-transmission-time'] ?? null,
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ]);

        return $response->successful()
            && $response->json('verification_status') === 'SUCCESS';
    }

    private function accessToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post("{$this->baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            throw new RuntimeException('PayPal auth failed: '.$response->body());
        }

        return $this->token = (string) $response->json('access_token');
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->baseUrl($this->baseUrl);
    }
}
