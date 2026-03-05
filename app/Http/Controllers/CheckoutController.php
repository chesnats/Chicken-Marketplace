<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    private const ALLOWED_PAYMENT_METHODS = ['cod', 'gcash', 'paymaya', 'bank_transfer', 'otc'];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => ['required', 'string', Rule::in(self::ALLOWED_PAYMENT_METHODS)],
        ]);

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $cartItems = Cart::with('listing')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Your cart is empty.');
        }

        return DB::transaction(function () use ($validated, $user, $cartItems) {
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $cartItems->sum(fn ($item) => $item->listing->price),
                'status' => 'pending',
                'payment_method' => strtolower(trim($validated['payment_method'])),
                'address' => $validated['address'],
                'phone' => $validated['phone'],
            ]);

            foreach ($cartItems as $item) {
                $order->items()->create([
                    'listing_id' => $item->listing_id,
                    'price' => $item->listing->price,
                ]);

                $item->delete();
            }

            return redirect()->route('dashboard')->with('success', 'Order placed successfully!');
        });
    }
}
