<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService
{
    private const BASE_URL = 'https://api.paymongo.com/v1';

    public function createGcashCheckoutSession(Order $order, Collection $cartItems): array
    {
        $secretKey = (string) config('services.paymongo.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo secret key is missing. Set PAYMONGO_SECRET_KEY in .env.');
        }

        $lineItems = $cartItems->map(function ($item) {
            $unitPrice = (int) round(((float) $item->listing->price) * 100);

            return [
                'currency' => 'PHP',
                'amount' => $unitPrice,
                'name' => (string) ($item->listing->breed ?? 'Chicken listing'),
                'quantity' => 1,
            ];
        })->values()->all();

        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->asJson()
            ->post($this->baseUrl().'/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'description' => 'Chicken Marketplace Order #'.$order->id,
                        'line_items' => $lineItems,
                        'payment_method_types' => ['gcash'],
                        'success_url' => route('buyer.orders.index', [
                            'payment' => 'success',
                            'order' => $order->id,
                        ]),
                        'cancel_url' => route('cart.index', [
                            'payment' => 'cancelled',
                            'order' => $order->id,
                        ]),
                        'metadata' => [
                            'order_id' => (string) $order->id,
                            'user_id' => (string) $order->user_id,
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $message = data_get($response->json(), 'errors.0.detail', 'Unable to create PayMongo checkout session.');
            throw new RuntimeException($message);
        }

        $sessionId = (string) $response->json('data.id');
        $checkoutUrl = (string) $response->json('data.attributes.checkout_url');

        if ($sessionId === '' || $checkoutUrl === '') {
            throw new RuntimeException('PayMongo response did not include checkout session details.');
        }

        return [
            'id' => $sessionId,
            'checkout_url' => $checkoutUrl,
        ];
    }

    public function retrieveCheckoutSession(string $checkoutSessionId): array
    {
        $secretKey = (string) config('services.paymongo.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo secret key is missing.');
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->get($this->baseUrl().'/checkout_sessions/'.$checkoutSessionId);

        if (! $response->successful()) {
            $message = data_get($response->json(), 'errors.0.detail', 'Unable to retrieve PayMongo checkout session.');
            throw new RuntimeException($message);
        }

        return $response->json();
    }

    private function baseUrl(): string
    {
        return (string) config('services.paymongo.base_url', self::BASE_URL);
    }
}
