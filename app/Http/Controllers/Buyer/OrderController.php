<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        // Use Auth::id() instead of auth()->id()
        $myOrders = OrderItem::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id()); 
            })
            ->with(['listing.user', 'order'])
            ->latest()
            ->get();

        // Use Auth::user() and check if it exists before updating
        $user = Auth::user();
        if ($user) {
            $user->update(['last_orders_check_at' => now()]);
        }

        return Inertia::render('Buyer/Orders', ['myOrders' => $myOrders]);
    }
    public function destroy($id)
    {
        $item = OrderItem::whereHas('order', function ($query) {
            $query->where('user_id', Auth::id()); 
        })->findOrFail($id);

        // If it's still pending, we allow "Cancellation" (Delete from DB)
        // If it's delivered, we allow "Delete from view" (Delete from DB)
        if ($item->status === 'pending' || $item->status === 'delivered') {
            $item->delete();
            return redirect()->back()->with('success', 'Order updated successfully.');
        }

        return redirect()->back()->with('error', 'Cannot cancel an order that is already being processed.');
    }
    public function updateStatus(Request $request, $id)
    {
        $item = OrderItem::whereHas('order', function ($query) {
            $query->where('user_id', Auth::id()); 
        })->with(['listing.user'])->findOrFail($id);

        $request->validate([
            'status' => 'required|in:delivered'
        ]);

        $item->update(['status' => 'delivered']);

        // Notify the Seller that the buyer received the order
        $item->listing->user->notify(new OrderStatusNotification($item, 'delivered'));

        return redirect()->back()->with('success', 'Order marked as received!');
    }
}