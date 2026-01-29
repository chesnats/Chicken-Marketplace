<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $messages = Message::with(['sender','receiver','listing'])
            ->where(function($q) use ($user) {
                $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
            })->latest()->get();

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'listing_id' => 'nullable|integer|exists:listings,id',
            'content' => 'required|string|min:1',
        ]);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'listing_id' => $validated['listing_id'] ?? null,
            'content' => $validated['content'],
        ]);

        return response()->json($message, 201);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        if ($request->user()->id !== $message->sender_id && $request->user()->id !== $message->receiver_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $message->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
