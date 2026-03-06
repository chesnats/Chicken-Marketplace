<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Listing;
use App\Models\OrderItem;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $composeReceiverId = request()->integer('compose_user');
        $composeListingId = request()->integer('compose_listing');
            
            // 💡 ADD THIS: Mark everything as read when the inbox is opened
            $allMessages = Message::where('sender_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->with(['sender', 'receiver', 'listing'])
                ->latest()
                ->get();

            // ... rest of your grouping logic stays the same
            $conversations = $allMessages->groupBy(function ($message) use ($userId) {
                return $message->sender_id === $userId ? $message->receiver_id : $message->sender_id;
            })->map(function ($msgs) use ($userId) {
                return [
                    'last_message' => $msgs->first(),
                    'other_user' => $msgs->first()->sender_id === $userId ? $msgs->first()->receiver : $msgs->first()->sender,
                    'messages' => $msgs->reverse()->values(),
                ];
            })->values();

            // 3. NOW update the database to clear the Navbar count for the NEXT request
            Message::where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

            $composeConversation = null;
            if ($composeReceiverId && $composeListingId) {
                $eligibleOrder = OrderItem::where('listing_id', $composeListingId)
                    ->whereHas('listing', fn ($query) => $query->where('user_id', $userId))
                    ->whereHas('order', fn ($query) => $query->where('user_id', $composeReceiverId))
                    ->with('order.user')
                    ->first();

                if ($eligibleOrder?->order?->user) {
                    $composeConversation = [
                        'other_user' => $eligibleOrder->order->user,
                        'listing_id' => $composeListingId,
                    ];
                }
            }

            return Inertia::render('Messages/Index', [
                'conversations' => $conversations,
                'composeConversation' => $composeConversation,
            ]);
        }

    /**
     * Mark messages from a specific conversation as read.
     */
        public function markAsRead($otherUserId)
        {
            Message::where('sender_id', $otherUserId)
                ->where('receiver_id', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return back(); 
        }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'listing_id'  => 'required|integer|exists:listings,id',
            'content'     => 'nullable|string|required_without:media',
            'media'       => [
                'nullable',
                'file',
                'required_without:content',
                'max:102400',
                'mimes:jpg,jpeg,png,webp,gif,heic,heif,mp4,mov,webm',
            ],
        ]);

        $senderId = Auth::id();
        $receiverId = (int) $validated['receiver_id'];
        $listingId = (int) $validated['listing_id'];

        if ($receiverId === $senderId) {
            abort(422, 'Invalid receiver.');
        }

        $sellerOwnsListing = OrderItem::where('listing_id', $listingId)
            ->whereHas('listing', fn ($query) => $query->where('user_id', $senderId))
            ->exists();

        if ($sellerOwnsListing) {
            $isBuyerOfListing = OrderItem::where('listing_id', $listingId)
                ->whereHas('order', fn ($query) => $query->where('user_id', $receiverId))
                ->exists();

            if (! $isBuyerOfListing) {
                abort(403, 'You can only message buyers who purchased this listing.');
            }
        } else {
            $isReceiverListingOwner = Listing::whereKey($listingId)
                ->where('user_id', $receiverId)
                ->exists();

            if (! $isReceiverListingOwner) {
                abort(403, 'Invalid conversation target for this listing.');
            }
        }

        $mediaPath = null;
        $mediaType = null;
        $mediaOriginalName = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaPath = $file->store('messages', 'public');
            $mediaOriginalName = $file->getClientOriginalName();
            $mediaType = str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'video';
        }

        $message = Message::create([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'listing_id'  => $listingId,
            'content'     => trim((string) ($validated['content'] ?? '')) ?: '[Attachment]',
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'media_original_name' => $mediaOriginalName,
            'is_read'     => false,
        ]);

        $message->load(['sender:id,name', 'receiver:id,name']);
        broadcast(new MessageSent($message));

        return redirect()->back();
    }

    public function destroy(Message $message)
    {
        if ($message->sender_id !== Auth::id() && $message->receiver_id !== Auth::id()) {
            abort(403);
        }
        
        $message->delete();
        return redirect()->back();
    }

    public function deleteConversation($otherUserId)
    {
        $authId = Auth::id();

        Message::where(function($query) use ($authId, $otherUserId) {
            $query->where('sender_id', $authId)->where('receiver_id', $otherUserId);
        })->orWhere(function($query) use ($authId, $otherUserId) {
            $query->where('sender_id', $otherUserId)->where('receiver_id', $authId);
        })->delete();

        return redirect()->route('messages.index');
    }

    public function startBuyerConversation(int $orderItemId): RedirectResponse
    {
        $sellerId = Auth::id();

        $orderItem = OrderItem::with(['order.user', 'listing'])
            ->whereHas('listing', fn ($query) => $query->where('user_id', $sellerId))
            ->findOrFail($orderItemId);

        $buyerId = $orderItem->order?->user_id;
        if (! $buyerId) {
            abort(404, 'Buyer not found.');
        }

        return redirect()->route('messages.index', [
            'compose_user' => $buyerId,
            'compose_listing' => $orderItem->listing_id,
        ]);
    }
}
