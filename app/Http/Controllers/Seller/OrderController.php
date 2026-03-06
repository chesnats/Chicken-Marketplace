<?php

namespace App\Http\Controllers\Seller;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\OrderItem;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $sellerId = Auth::id();

        $incomingOrders = OrderItem::whereHas('listing', function ($query) use ($sellerId) {
            $query->where('user_id', $sellerId);
        })
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', ['pending', 'paid']);
            })
            ->with(['order.user', 'listing'])
            ->latest()
            ->get();

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->update(['last_orders_check_at' => now()]);
        }

        return Inertia::render('Seller/Orders', [
            'incomingOrders' => $incomingOrders,
        ]);
    }

    public function updateStatus(Request $request, $orderItemId): RedirectResponse
    {
        /** @var \App\Models\OrderItem $item */
        $item = OrderItem::with(['listing', 'order.user'])->findOrFail($orderItemId);

        if ($item->listing?->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate(['status' => 'required|in:accepted,on_delivery,delivered']);
        $newStatus = $request->status;
        $item->update(['status' => $newStatus]);

        /** @var \App\Models\User|null $authUser */
        $authUser = Auth::user();
        $buyer = $item->order?->user;

        if ($newStatus === 'delivered') {
            $authUser?->notify(new OrderStatusNotification($item, 'delivered'));
            $buyer?->notify(new OrderStatusNotification($item, 'delivered'));
        } else {
            $buyer?->notify(new OrderStatusNotification($item, $newStatus));
        }

        if ($authUser && $buyer && $item->listing_id) {
            $autoMessage = Message::create([
                'sender_id' => $authUser->id,
                'receiver_id' => $buyer->id,
                'listing_id' => $item->listing_id,
                'content' => $this->buildStatusMessage($item, $newStatus),
                'is_read' => false,
            ]);

            $autoMessage->load(['sender:id,name', 'receiver:id,name']);
            broadcast(new MessageSent($autoMessage));
        }

        return redirect()->back();
    }

    private function buildStatusMessage(OrderItem $item, string $status): string
    {
        $breed = $item->listing?->breed ?? 'your order';
        $prefix = "Thank you for purchasing {$breed}. ";

        return match ($status) {
            'accepted' => $prefix . 'Your order has been accepted by the seller.',
            'on_delivery' => $prefix . 'Your order is already picked up and on the way.',
            'delivered' => $prefix . 'Your order has been marked as delivered.',
            default => $prefix . 'Your order status has been updated.',
        };
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
