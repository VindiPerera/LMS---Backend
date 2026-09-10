<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Direct card payments via PayPal's Orders v2 API using `payment_source.
 * card` — PayPal's "Advanced Credit and Debit Card Payments" product. This
 * is what lets a user type their Visa/Mastercard details straight into
 * this app and pay, with no redirect to paypal.com and no PayPal account
 * needed on the payer's side.
 *
 * IMPORTANT — this account-level feature isn't on by default. It needs to
 * be requested/enabled for your PayPal Business account (PayPal reviews
 * and approves it per-merchant, and availability varies by country) before
 * any of this will actually succeed rather than returning an error from
 * PayPal. See PAYPAL_CLIENT_ID/PAYPAL_CLIENT_SECRET in .env.
 *
 * PCI-DSS note: raw card data passes through this server on its way to
 * PayPal (that's inherent to this integration style, not something code
 * can avoid) — so this server is in PCI-DSS SAQ D scope. Nothing here
 * logs, stores, or persists the raw card fields anywhere; they exist only
 * in memory for the duration of a single request. Longer-term, ask
 * whoever handles compliance for this business whether that's acceptable
 * or whether a client-side-tokenizing alternative is needed instead.
 */
class PayPalService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $mode = config('services.paypal.mode', 'sandbox');
        $this->baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $this->clientId = (string) config('services.paypal.client_id');
        $this->clientSecret = (string) config('services.paypal.client_secret');
    }

    /**
     * OAuth2 client-credentials token, cached for its lifetime (PayPal
     * tokens last ~9 hours) minus a safety margin.
     */
    private function getAccessToken(): string
    {
        return Cache::remember('paypal_access_token', now()->addHours(8), function () {
            if ($this->clientId === '' || $this->clientSecret === '') {
                throw new RuntimeException(
                    'PayPal is not configured — set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in .env ' .
                    '(developer.paypal.com -> Apps & Credentials).'
                );
            }

            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                throw new RuntimeException('PayPal auth failed: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Creates an order with the card as the payment source and, when
     * PayPal doesn't auto-capture it, captures it immediately after —
     * this project only ever wants an immediate one-time charge (VIP
     * subscription), never a delayed/authorize-only flow.
     *
     * @param array{number: string, expiry: string, security_code: string, name: string} $card
     *        `expiry` must be "YYYY-MM".
     * @return array{orderId: string, status: string} status is PayPal's
     *         order status string, e.g. "COMPLETED".
     *
     * @throws RuntimeException on any failure — the caller (PaymentController)
     *         is responsible for turning that into a clean API response and
     *         making sure the exception message itself never leaks back to
     *         the client with raw card data inside it (PayPal's own error
     *         responses don't echo the card number back, so this is safe).
     */
    public function createAndCaptureCardOrder(
        array $card,
        float $amount,
        string $currency,
        string $description,
    ): array {
        $token = $this->getAccessToken();
        $requestId = (string) Str::uuid(); // PayPal-Request-Id: makes a retry idempotent, never double-charges.

        $orderResponse = Http::withToken($token)
            ->withHeaders(['PayPal-Request-Id' => $requestId])
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'description' => $description,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'payment_source' => [
                    'card' => [
                        'number' => $card['number'],
                        'expiry' => $card['expiry'],
                        'security_code' => $card['security_code'],
                        'name' => $card['name'],
                    ],
                ],
            ]);

        if (!$orderResponse->successful()) {
            throw new RuntimeException('PayPal order creation failed: ' . $this->summarizeError($orderResponse));
        }

        $order = $orderResponse->json();
        $orderId = $order['id'];
        $status = $order['status'] ?? 'UNKNOWN';

        // Card payment_source orders are very often auto-captured by PayPal
        // already (status comes back COMPLETED directly) — only call
        // capture explicitly if it's still sitting as APPROVED.
        if ($status !== 'COMPLETED') {
            $captureResponse = Http::withToken($token)
                ->withHeaders(['PayPal-Request-Id' => $requestId . '-capture'])
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            if (!$captureResponse->successful()) {
                throw new RuntimeException('PayPal capture failed: ' . $this->summarizeError($captureResponse));
            }

            $status = $captureResponse->json('status', $status);
        }

        return ['orderId' => $orderId, 'status' => $status];
    }

    /** Pulls PayPal's structured error `name`/`message` without ever
     * including the request body (which would contain the card data) in
     * whatever gets logged or surfaced. */
    private function summarizeError($response): string
    {
        $body = $response->json();
        $name = $body['name'] ?? 'UNKNOWN_ERROR';
        $message = $body['message'] ?? $response->body();
        $firstIssue = $body['details'][0]['description'] ?? null;
        return $firstIssue ? "{$name}: {$firstIssue}" : "{$name}: {$message}";
    }
}
