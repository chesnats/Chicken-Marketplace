<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => 'required|in:cod,gcash,paymaya',
        ]);

        $user = auth()->user();
        $cartItems = Cart::with('listing')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Your cart is empty.');
        }

        // Use a DB Transaction to ensure data integrity
        return DB::transaction(function () use ($request, $user, $cartItems) {
            
            // 1. Create the main Order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $cartItems->sum(fn($item) => $item->listing->price),
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            // 2. Move items to OrderItems and clear cart
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'listing_id' => $item->listing_id,
                    'price' => $item->listing->price,
                ]);
                $item->delete(); // Remove from cart
            }

            // 3. Logic for E-Wallets
            if ($request->payment_method !== 'cod') {
                // Here you would integrate PayMongo, Xendit, or PayPal
                // For now, we'll just redirect to a success page
            }

            return redirect()->route('dashboard')->with('success', 'Order placed successfully!');
        });
    }
}