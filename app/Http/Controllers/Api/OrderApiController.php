<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->is('api/seller*')) {
            if (!$user || $user->role !== 'seller') {
                return response()->json(['message' => 'Seller access only'], 403);
            }
            // Seller: orders where any order_items' listing belongs to seller
            $orders = Order::whereHas('items.listing', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with('items.listing')->latest()->get();
        } else {
            if (!$user || $user->role !== 'buyer') {
                return response()->json(['message' => 'Buyer access only'], 403);
            }
            // Buyer: orders placed by user
            $orders = Order::with('items.listing')->where('user_id', $user->id)->latest()->get();
        }

        return response()->json($orders);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $user = $request->user();

        if ($request->is('api/seller*')) {
            if (!$user || $user->role !== 'seller') {
                return response()->json(['message' => 'Seller access only'], 403);
            }

            $ownsOrder = $order->items()
                ->whereHas('listing', fn ($q) => $q->where('user_id', $user->id))
                ->exists();

            if (!$ownsOrder) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $validated = $request->validate(['status' => 'required|in:accepted,on_delivery,delivered']);
            $order->status = $validated['status'];
            $order->save();

            return response()->json($order);
        }

        if ($request->is('api/buyer*')) {
            if (!$user || $user->role !== 'buyer') {
                return response()->json(['message' => 'Buyer access only'], 403);
            }

            if ($order->user_id !== $user->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $order->status = 'delivered';
            $order->save();

            return response()->json($order);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $user = $request->user();

        if ($request->is('api/seller*')) {
            if (!$user || $user->role !== 'seller') {
                return response()->json(['message' => 'Seller access only'], 403);
            }

            $ownsOrder = $order->items()
                ->whereHas('listing', fn ($q) => $q->where('user_id', $user->id))
                ->exists();

            if (!$ownsOrder) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        } else {
            if (!$user || $user->role !== 'buyer' || $user->id !== $order->user_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $order->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
