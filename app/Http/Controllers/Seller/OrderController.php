<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Notifications\OrderStatusNotification;

class OrderController extends Controller
{
    public function index()
    {
        $sellerId = auth()->id();

        // Find all order items that belong to THIS seller's listings
        $incomingOrders = OrderItem::whereHas('listing', function ($query) use ($sellerId) {
            $query->where('user_id', $sellerId);
        })
        ->with(['order.user', 'listing'])
        ->latest()
        ->get();
        auth()->user()->update(['last_orders_check_at' => now()]);
        return Inertia::render('Seller/Orders', [
            'incomingOrders' => $incomingOrders
        ]);
    }

    /**
     * Update the status of a specific order item.
     * Logic: pending -> accepted -> on_delivery -> delivered
     */
        public function updateStatus(Request $request, $orderItemId)
        {
            $item = OrderItem::with(['listing', 'order.user'])->findOrFail($orderItemId);
            
            if ($item->listing->user_id !== auth()->id()) { abort(403); }

            $request->validate(['status' => 'required|in:accepted,on_delivery,delivered']);
            $item->update(['status' => $request->status]);

            // NOTIFICATION LOGIC
            if ($request->status === 'delivered') {
                // Notify the Seller (themselves) that it's done
                auth()->user()->notify(new OrderStatusNotification($item, 'delivered'));
                // Notify the Buyer
                $item->order->user->notify(new OrderStatusNotification($item, 'delivered'));
            } else {
                // Notify the Buyer for 'accepted' or 'on_delivery'
                $item->order->user->notify(new OrderStatusNotification($item, $request->status));
            }

            return redirect()->back();
        }
        public function destroy($id)
        {
            // Find the item only if it belongs to a listing owned by the seller
            $item = OrderItem::whereHas('listing', function ($query) {
                $query->where('user_id', auth()->id());
            })->findOrFail($id);

            // Only allow deletion if it's already delivered
            if ($item->status === 'delivered') {
                $item->delete();
                return redirect()->back()->with('success', 'Order record removed.');
            }

            return redirect()->back()->with('error', 'You can only delete orders that are already delivered.');
        }
}