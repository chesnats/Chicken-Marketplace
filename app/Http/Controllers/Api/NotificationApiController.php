<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notes = DB::table('notifications')->where('notifiable_id', $user->id)->orderBy('created_at', 'desc')->get();
        return response()->json($notes);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        DB::table('notifications')->where('notifiable_id', $user->id)->update(['read_at' => now()]);
        return response()->json(['message' => 'Marked all as read']);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $deleted = DB::table('notifications')->where('id', $id)->where('notifiable_id', $user->id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['message' => 'Deleted']);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $user = $request->user();
        DB::table('notifications')->where('notifiable_id', $user->id)->delete();
        return response()->json(['message' => 'All notifications deleted']);
    }
}
