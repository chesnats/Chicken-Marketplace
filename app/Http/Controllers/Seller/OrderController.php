<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\Auth; // ✅ Use Facade to stop red lines
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function index()
    {
        // Change auth()->id() to Auth::id() to satisfy the editor
        $sellerId = Auth::id();

        $incomingOrders = OrderItem::whereHas('listing', function ($query) use ($sellerId) {
            $query->where('user_id', $sellerId);
        })
        ->with(['order.user', 'listing'])
        ->latest()
        ->get();

        // Type hint the user to stop "Undefined method user"
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user) {
            $user->update(['last_orders_check_at' => now()]);
        }

        return Inertia::render('Seller/Orders', [
            'incomingOrders' => $incomingOrders
        ]);
    }

    public function updateStatus(Request $request, $orderItemId): RedirectResponse
    {
        // Explicitly type hint $item as an OrderItem
        /** @var \App\Models\OrderItem $item */
        $item = OrderItem::with(['listing', 'order.user'])->findOrFail($orderItemId);
        
        if ($item->listing?->user_id !== Auth::id()) { 
            abort(403); 
        }

        $request->validate(['status' => 'required|in:accepted,on_delivery,delivered']);
        $item->update(['status' => $request->status]);

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Notify the Buyer (Safely handle the relationship)
        $buyer = $item->order?->user;

        if ($request->status === 'delivered') {
            // Notify Seller
            $authUser?->notify(new OrderStatusNotification($item, 'delivered'));
            // Notify Buyer
            $buyer?->notify(new OrderStatusNotification($item, 'delivered'));
        } else {
            // Notify Buyer for other statuses
            $buyer?->notify(new OrderStatusNotification($item, $request->status));
        }

        return redirect()->back();
    }

    public function destroy($id): RedirectResponse
    {
        $item = OrderItem::whereHas('listing', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        if ($item->status === 'delivered') {
            $item->delete();
            return redirect()->back()->with('success', 'Order record removed.');
        }

        return redirect()->back()->with('error', 'You can only delete orders that are already delivered.');
    }
}