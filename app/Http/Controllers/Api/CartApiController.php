<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = Cart::with('listing')->where('user_id', $user->id)->get();
        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_id' => 'required|integer|exists:listings,id',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $cart = Cart::create([
            'user_id' => $request->user()->id,
            'listing_id' => $validated['listing_id'],
            'quantity' => $validated['quantity'] ?? 1,
        ]);

        return response()->json($cart, 201);
    }

    public function destroy(Request $request, Cart $cart): JsonResponse
    {
        if ($request->user()->id !== $cart->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cart->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
