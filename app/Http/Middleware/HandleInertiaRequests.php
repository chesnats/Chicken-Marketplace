<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                
                // 💬 Chat Messages Count
                'unreadMessagesCount' => $user 
                    ? \App\Models\Message::where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count()
                    : 0,
                'cartCount' => $user ? \App\Models\Cart::where('user_id', $user->id)->count() : 0,
                // 🔔 Notifications Data
                'unreadNotificationsCount' => $user 
                    ? $user->unreadNotifications()->count() 
                    : 0,

                'notifications' => $user 
                    ? $user->notifications()->latest()->limit(20)->get() 
                    : [],
                'sellerOrderCount' => $user && $user->role === 'seller' 
                    ? \App\Models\OrderItem::whereHas('listing', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->whereIn('status', ['pending', 'accepted', 'on_delivery'])
                    // 💡 ONLY count if the order was updated AFTER the user last checked the list
                    ->where('updated_at', '>', $user->last_orders_check_at ?? '2000-01-01')
                    ->count() 
                    : 0,

                'buyerOrderCount' => $user && $user->role === 'buyer' 
                    ? \App\Models\OrderItem::whereHas('order', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->whereIn('status', ['pending', 'accepted', 'on_delivery'])
                    // 💡 ONLY count if the status changed AFTER the user last checked the list
                    ->where('updated_at', '>', $user->last_orders_check_at ?? '2000-01-01')
                    ->count() 
                    : 0,
                    
            ],
            // Flash messages for success/error alerts
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}