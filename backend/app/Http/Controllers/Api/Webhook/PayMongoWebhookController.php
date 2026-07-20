<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ApplicationService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoService $payMongo, ApplicationService $applications)
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature') ?? $request->header('PayMongo-Signature');

        if (! $payMongo->verifyWebhookSignature($payload, $signature)) {
            Log::warning('PayMongo webhook signature mismatch');

            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('data.attributes.type') ?? $request->input('data.attributes.data.attributes.type');
        $eventType = $request->input('data.attributes.type');

        // Structure: data.attributes.type = checkout_session.payment.paid
        // nested resource in data.attributes.data
        $resource = $request->input('data.attributes.data');
        $checkoutId = $resource['id'] ?? null;
        $attrs = $resource['attributes'] ?? [];

        if ($eventType === 'checkout_session.payment.paid' || ($attrs['status'] ?? null) === 'paid') {
            $payment = Payment::where('paymongo_checkout_id', $checkoutId)->first();

            if (! $payment && isset($attrs['metadata']['payment_id'])) {
                $payment = Payment::find($attrs['metadata']['payment_id']);
            }

            if ($payment) {
                $method = 'paymongo';
                $payments = $attrs['payments'] ?? [];
                if (! empty($payments[0]['attributes']['source']['type'])) {
                    $source = $payments[0]['attributes']['source']['type'];
                    $method = match ($source) {
                        'gcash' => 'paymongo_gcash',
                        'qrph' => 'paymongo_qrph',
                        'grab_pay' => 'paymongo_grab_pay',
                        'card' => 'paymongo_card',
                        default => 'paymongo_'.$source,
                    };
                }

                $payment->paymongo_payment_id = $payments[0]['id'] ?? $payment->paymongo_payment_id;
                $payment->save();

                $applications->markPaid($payment->application, $payment, $method, 'PayMongo webhook payment paid');
            }
        }

        return response()->json(['received' => true]);
    }
}
