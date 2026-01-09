<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display the buyer's cart.
     */
    public function index()
    {
        return Inertia::render('Cart/Index', [
            'cartItems' => Auth::user()->carts()
                ->with(['listing.user']) // Load the chicken details AND the seller info
                ->latest()
                ->get()
        ]);
    }

    /**
     * Add a chicken listing to the cart.
     */
    public function store(Request $request)
    {
        $request->validate(['listing_id' => 'required|exists:listings,id']);

        // Only buyers can add to cart
        if (Auth::user()->role !== 'buyer') {
            return redirect()->back()->with('error', 'Only buyers can use the cart.');
        }

        Cart::firstOrCreate([
            'user_id' => Auth::id(),
            'listing_id' => $request->listing_id
        ]);

        return redirect()->back();
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Cart $cart)
    {
        // Ensure the user owns this cart item
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }
        
        $cart->delete();
        return redirect()->back();
    }
}