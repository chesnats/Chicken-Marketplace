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
            // Seller: orders where any order_items' listing belongs to seller
            $orders = Order::whereHas('items.listing', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with('items.listing')->latest()->get();
        } else {
            // Buyer: orders placed by user
            $orders = Order::with('items.listing')->where('user_id', $user->id)->latest()->get();
        }

        return response()->json($orders);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $validated = $request->validate(['status' => 'required|string']);
        $order->status = $validated['status'];
        $order->save();
        return response()->json($order);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        if ($request->user()->id !== $order->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
