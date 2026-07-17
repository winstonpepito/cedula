<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayMongoService
{
    private string $baseUrl = 'https://api.paymongo.com/v1';

    public function isEnabled(): bool
    {
        return (bool) config('services.paymongo.enabled')
            && filled(config('services.paymongo.secret_key'));
    }

    public function createCheckoutSession(Application $application, Payment $payment, array $paymentMethods = ['card', 'gcash']): array
    {
        if (! $this->isEnabled()) {
            return $this->createMockCheckout($application, $payment);
        }

        $amountCentavos = (int) round(((float) $payment->amount) * 100);
        $successUrl = rtrim(config('app.frontend_url'), '/').'/receipt/'.$application->tracking_number.'?paid=1';
        $cancelUrl = rtrim(config('app.frontend_url'), '/').'/pay/'.$application->tracking_number.'?cancelled=1';

        $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
            ->acceptJson()
            ->post($this->baseUrl.'/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'send_email_receipt' => false,
                        'show_description' => true,
                        'show_line_items' => true,
                        'description' => 'eCedula '.$application->tracking_number,
                        'line_items' => [[
                            'currency' => 'PHP',
                            'amount' => $amountCentavos,
                            'name' => 'Community Tax Certificate',
                            'quantity' => 1,
                            'description' => 'CTC application '.$application->tracking_number,
                        ]],
                        'payment_method_types' => $paymentMethods,
                        'success_url' => $successUrl,
                        'cancel_url' => $cancelUrl,
                        'reference_number' => $application->tracking_number,
                        'metadata' => [
                            'tracking_number' => $application->tracking_number,
                            'payment_id' => (string) $payment->id,
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayMongo checkout failed: '.$response->body());
        }

        $data = $response->json('data');

        return [
            'checkout_id' => $data['id'] ?? null,
            'checkout_url' => $data['attributes']['checkout_url'] ?? null,
            'raw' => $data,
            'mock' => false,
        ];
    }

    public function createMockCheckout(Application $application, Payment $payment): array
    {
        $token = Str::random(32);
        $url = rtrim(config('app.frontend_url'), '/').'/pay/'.$application->tracking_number.'/mock?token='.$token;

        return [
            'checkout_id' => 'mock_cs_'.$payment->id.'_'.$token,
            'checkout_url' => $url,
            'raw' => ['mock' => true],
            'mock' => true,
        ];
    }

    public function verifyWebhookSignature(string $payload, ?string $signatureHeader): bool
    {
        $secret = config('services.paymongo.webhook_secret');
        if (! $secret) {
            return ! $this->isEnabled();
        }

        if (! $signatureHeader) {
            return false;
        }

        // PayMongo sends: t=timestamp,te=test_signature,li=live_signature
        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key && $value) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['te'] ?? $parts['li'] ?? null;
        if (! $timestamp || ! $signature) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }
}
