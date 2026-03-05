<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $months = $this->buildMonths();
        $monthlyActivity = $this->buildMonthlyActivity($user, $months);
        $statusCounts = $this->buildStatusCounts($user);
        $insights = $this->buildInsights($user);

        return Inertia::render('Dashboard', [
            'userRole' => $user->role,
            'stats' => [
                'listings_count' => $user->role === 'seller' ? $user->listings()->count() : 0,
                'cart_count' => $user->role === 'buyer' ? $user->carts()->count() : 0,
                'total_income' => $this->calculateTotalIncome($user),
                'registered_buyers' => (int) User::where('role', 'buyer')->count(),
                'items_sold' => $this->calculateItemsSold($user),
            ],
            'charts' => [
                'monthly' => [
                    'labels' => $months->map(fn (Carbon $month) => $month->format('M')),
                    'values' => $monthlyActivity,
                ],
                'order_status' => [
                    'labels' => $statusCounts->keys()->map(fn ($status) => ucfirst(str_replace('_', ' ', $status))),
                    'values' => $statusCounts->values(),
                ],
            ],
            'insights' => $insights,
        ]);
    }

    private function buildMonths(): Collection
    {
        return collect(range(5, 0))
            ->map(fn (int $offset) => Carbon::now()->startOfMonth()->subMonths($offset))
            ->push(Carbon::now()->startOfMonth());
    }

    private function buildMonthlyActivity(User $user, Collection $months): Collection
    {
        return $months->map(function (Carbon $monthStart) use ($user) {
            $monthEnd = (clone $monthStart)->endOfMonth();

            if ($user->role === 'seller') {
                return $user->listings()
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();
            }

            return $this->buyerOrderItemsQuery($user)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
        });
    }

    private function buildStatusCounts(User $user): Collection
    {
        if ($user->role === 'seller') {
            return $this->sellerOrderItemsQuery($user)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }

        return $this->buyerOrderItemsQuery($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    private function calculateTotalIncome(User $user): float
    {
        if ($user->role === 'seller') {
            return (float) $this->sellerOrderItemsQuery($user)
                ->where('status', 'delivered')
                ->sum('price');
        }

        return (float) $this->buyerOrderItemsQuery($user)->sum('price');
    }

    private function calculateItemsSold(User $user): int
    {
        if ($user->role === 'seller') {
            return (int) $this->sellerOrderItemsQuery($user)
                ->where('status', 'delivered')
                ->count();
        }

        return (int) $this->buyerOrderItemsQuery($user)->count();
    }

    private function buildInsights(User $user): array
    {
        if ($user->role === 'seller') {
            $sellerOrders = $this->sellerOrderItemsQuery($user);
            $ordersLast7Days = (clone $sellerOrders)->where('created_at', '>=', now()->subDays(7))->count();

            $uniqueBuyersLast7Days = (clone $sellerOrders)
                ->with('order:id,user_id')
                ->where('created_at', '>=', now()->subDays(7))
                ->get()
                ->pluck('order.user_id')
                ->filter()
                ->unique()
                ->count();

            $recentOrders = (clone $sellerOrders)
                ->with(['order.user:id,name', 'listing:id,breed'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (OrderItem $item) => $this->formatRecentOrder($item, true));

            return [
                'recent_buyer_activity' => [
                    'orders_last_7_days' => $ordersLast7Days,
                    'unique_buyers_last_7_days' => $uniqueBuyersLast7Days,
                ],
                'recent_orders' => $recentOrders,
            ];
        }

        $buyerOrders = $this->buyerOrderItemsQuery($user);
        $ordersLast7Days = (clone $buyerOrders)->where('created_at', '>=', now()->subDays(7))->count();

        $recentOrders = (clone $buyerOrders)
            ->with(['listing:id,breed'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (OrderItem $item) => $this->formatRecentOrder($item, false));

        return [
            'recent_buyer_activity' => [
                'orders_last_7_days' => $ordersLast7Days,
                'unique_buyers_last_7_days' => 0,
            ],
            'recent_orders' => $recentOrders,
        ];
    }

    private function formatRecentOrder(OrderItem $item, bool $isSeller): array
    {
        return [
            'id' => $item->id,
            'breed' => $item->listing?->breed ?? 'Listing removed',
            'buyer_name' => $isSeller ? ($item->order?->user?->name ?? 'Unknown buyer') : 'You',
            'status' => ucfirst(str_replace('_', ' ', $item->status ?? 'pending')),
            'price' => $item->price,
            'created_at' => $item->created_at?->toDateTimeString(),
        ];
    }

    private function sellerOrderItemsQuery(User $user)
    {
        return OrderItem::whereHas('listing', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });
    }

    private function buyerOrderItemsQuery(User $user)
    {
        return OrderItem::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });
    }
}
