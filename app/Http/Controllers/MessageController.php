<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
            
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

            return Inertia::render('Messages/Index', [
                'conversations' => $conversations
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
        $request->validate([
            'receiver_id' => 'required',
            'listing_id'  => 'required',
            'content'     => 'required|string',
        ]);

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->input('receiver_id'),
            'listing_id'  => $request->input('listing_id'),
            'content'     => $request->input('content'),
            'is_read'     => false,
        ]);

        broadcast(new MessageSent($message))->toOthers();
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
}