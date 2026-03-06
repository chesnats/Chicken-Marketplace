<?php

namespace App\Http\Controllers\Buyer;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\OrderItem;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        // Fetch orders belonging to the buyer
        $myOrders = OrderItem::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id()); 
            })
            ->with([
                // Include listing details even if soft-deleted by seller
                'listing' => function ($query) {
                    $query->withTrashed()->with('user'); 
                }, 
                'order'
            ])
            ->latest()
            ->get();

        // Clear the notification badge count for the buyer
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

        // ✅ Null-safe check before notifying
        if ($item->listing && $item->listing->user) {
            $item->listing->user->notify(new OrderStatusNotification($item, 'delivered'));

            $buyer = Auth::user();
            if ($buyer) {
                $autoMessage = Message::create([
                    'sender_id' => $buyer->id,
                    'receiver_id' => $item->listing->user->id,
                    'listing_id' => $item->listing_id,
                    'content' => 'Thank you. I already received the order.',
                    'is_read' => false,
                ]);

                $autoMessage->load(['sender:id,name', 'receiver:id,name']);
                broadcast(new MessageSent($autoMessage));
            }
        }

        return redirect()->back()->with('success', 'Order marked as received!');
    }
}
