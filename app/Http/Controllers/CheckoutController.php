<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class CheckoutController extends Controller
{
    private const ALLOWED_PAYMENT_METHODS = ['cod', 'gcash'];

    public function __construct(private readonly PayMongoService $payMongoService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => ['required', 'string'],
        ]);
        $paymentMethod = $this->normalizePaymentMethod($validated['payment_method']);

        validator(
            ['payment_method' => $paymentMethod],
            ['payment_method' => ['required', Rule::in(self::ALLOWED_PAYMENT_METHODS)]]
        )->validate();

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $cartItems = Cart::with('listing')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Your cart is empty.');
        }

        try {
            $checkoutUrl = null;

            DB::transaction(function () use ($validated, $user, $cartItems, $paymentMethod, &$checkoutUrl) {
                $isGcash = $paymentMethod === 'gcash';
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => $cartItems->sum(fn ($item) => $item->listing->price),
                    'status' => $isGcash ? 'awaiting_payment' : 'pending',
                    'payment_method' => $paymentMethod,
                    'payment_status' => $isGcash ? 'awaiting_payment' : 'pending',
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                ]);

                foreach ($cartItems as $item) {
                    $order->items()->create([
                        'listing_id' => $item->listing_id,
                        'price' => $item->listing->price,
                        'status' => $isGcash ? 'awaiting_payment' : 'pending',
                    ]);

                    $item->delete();
                }

                if ($isGcash) {
                    $session = $this->payMongoService->createGcashCheckoutSession($order, $cartItems);
                    $order->update([
                        'paymongo_checkout_session_id' => $session['id'],
                    ]);
                    $checkoutUrl = $session['checkout_url'];
                }
            });

            if ($checkoutUrl) {
                return Inertia::location($checkoutUrl);
            }

            return redirect()->route('buyer.orders.index')->with('success', 'Order placed successfully!');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function normalizePaymentMethod(string $paymentMethod): string
    {
        return strtolower(trim($paymentMethod));
    }
}
