<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function payMongo(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventType = strtolower((string) data_get($payload, 'data.attributes.type', ''));

        if ($eventType === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $checkoutSessionId = $this->extractCheckoutSessionId($payload);
        $paymentReference = (string) $this->findValueRecursively($payload, [
            'payment_intent_id',
            'payment_id',
            'payment_reference',
            'id',
        ]);

        if ($checkoutSessionId === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $order = Order::query()
            ->where('paymongo_checkout_session_id', $checkoutSessionId)
            ->first();

        if (! $order) {
            return response()->json(['status' => 'not_found'], 200);
        }

        if (str_contains($eventType, 'paid')) {
            $order->update([
                'status' => 'pending',
                'payment_status' => 'paid',
                'paymongo_payment_reference' => $paymentReference !== '' ? $paymentReference : $checkoutSessionId,
            ]);

            $order->items()->where('status', 'awaiting_payment')->update([
                'status' => 'pending',
            ]);
        }

        if (str_contains($eventType, 'failed') || str_contains($eventType, 'expired')) {
            $order->update([
                'payment_status' => 'failed',
                'paymongo_payment_reference' => $paymentReference !== '' ? $paymentReference : $checkoutSessionId,
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function extractCheckoutSessionId(array $payload): string
    {
        $candidates = [
            data_get($payload, 'data.attributes.data.id'),
            data_get($payload, 'data.attributes.data.attributes.checkout_session_id'),
            data_get($payload, 'data.attributes.checkout_session_id'),
            data_get($payload, 'data.id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        $found = $this->findValueRecursively($payload, ['checkout_session_id', 'checkout_session']);

        return is_string($found) ? trim($found) : '';
    }

    private function findValueRecursively(array $data, array $targetKeys): mixed
    {
        foreach ($data as $key => $value) {
            if (in_array((string) $key, $targetKeys, true) && is_scalar($value)) {
                return $value;
            }

            if (is_array($value)) {
                $found = $this->findValueRecursively($value, $targetKeys);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
