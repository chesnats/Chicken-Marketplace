<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mark all unread notifications for the user as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back();
    }
    public function markSelectedAsRead(Request $request)
    {
        // Validate that we received an array of IDs
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string' // Notification IDs are usually UUID strings
        ]);

        // Update the 'read_at' timestamp for the matching notifications
        $request->user()->unreadNotifications()
            ->whereIn('id', $request->ids)
            ->update(['read_at' => now()]);

        return back();
    }

    /**
     * Delete all notifications for the authenticated user.
     */
    public function destroyAll(Request $request)
    {
        $user = $request->user();
        $user->notifications()->delete();

        return back();
    }
}