<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        // ✅ 1. Update validation to include new payment methods
        $request->validate([
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => 'required|in:cod,gcash,paymaya,bank_transfer,otc',
        ]);

        // ✅ 2. Get the authenticated user safely
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // ✅ 3. Fetch cart items with listing data
        $cartItems = Cart::with('listing')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Your cart is empty.');
        }

        // Use a DB Transaction to ensure data integrity
        return DB::transaction(function () use ($request, $user, $cartItems) {
            
            // 4. Create the main Order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $cartItems->sum(fn($item) => $item->listing->price),
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            // 5. Move items to OrderItems and clear cart
            foreach ($cartItems as $item) {
                // ✅ Ensure your Order Model has the "items()" relationship defined
                $order->items()->create([
                    'listing_id' => $item->listing_id,
                    'price' => $item->listing->price,
                ]);
                
                $item->delete(); 
            }

            // 6. Logic for Non-COD payments
            if ($request->payment_method !== 'cod') {
                // Logic for GCash, Bank Transfer, etc. can go here
            }

            return redirect()->route('dashboard')->with('success', 'Order placed successfully!');
        });
    }
}