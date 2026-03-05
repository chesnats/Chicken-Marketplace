<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\CartController;   
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\NotificationController;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
	return redirect()->route('listings.index');
})->name('home');

Route::get('/dashboard', function () {
	$user = Auth::user();

	if (!$user) {
		return redirect()->route('login');
	}
    
	$months = collect(range(5, 0))
		->map(fn (int $offset) => Carbon::now()->startOfMonth()->subMonths($offset))
		->push(Carbon::now()->startOfMonth());

	$monthlyActivity = $months->map(function (Carbon $monthStart) use ($user) {
		$monthEnd = (clone $monthStart)->endOfMonth();

		if ($user->role === 'seller') {
			return $user->listings()
				->whereBetween('created_at', [$monthStart, $monthEnd])
				->count();
		}

		return OrderItem::whereHas('order', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		})
			->whereBetween('created_at', [$monthStart, $monthEnd])
			->count();
	});

	if ($user->role === 'seller') {
		$statusCounts = OrderItem::whereHas('listing', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		})
			->selectRaw('status, COUNT(*) as total')
			->groupBy('status')
			->pluck('total', 'status');

		$totalIncome = (float) OrderItem::whereHas('listing', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		})
			->where('status', 'delivered')
			->sum('price');

		$itemsSold = (int) OrderItem::whereHas('listing', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		})
			->where('status', 'delivered')
			->count();
	} else {
		$statusCounts = OrderItem::whereHas('order', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		})
			->selectRaw('status, COUNT(*) as total')
			->groupBy('status')
			->pluck('total', 'status');

		$totalIncome = (float) OrderItem::whereHas('order', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		})
			->sum('price');

		$itemsSold = (int) OrderItem::whereHas('order', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		})
			->count();
	}

	$registeredBuyers = (int) User::where('role', 'buyer')->count();

	if ($user->role === 'seller') {
		$sellerOrders = OrderItem::whereHas('listing', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		});

		$ordersLast7Days = (clone $sellerOrders)
			->where('created_at', '>=', now()->subDays(7))
			->count();

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
			->map(fn (OrderItem $item) => [
				'id' => $item->id,
				'breed' => $item->listing?->breed ?? 'Listing removed',
				'buyer_name' => $item->order?->user?->name ?? 'Unknown buyer',
				'status' => ucfirst(str_replace('_', ' ', $item->status ?? 'pending')),
				'price' => $item->price,
				'created_at' => $item->created_at?->toDateTimeString(),
			]);
	} else {
		$buyerOrders = OrderItem::whereHas('order', function ($query) use ($user) {
			$query->where('user_id', $user->id);
		});

		$ordersLast7Days = (clone $buyerOrders)
			->where('created_at', '>=', now()->subDays(7))
			->count();

		$uniqueBuyersLast7Days = 0;

		$recentOrders = (clone $buyerOrders)
			->with(['listing:id,breed'])
			->latest()
			->take(5)
			->get()
			->map(fn (OrderItem $item) => [
				'id' => $item->id,
				'breed' => $item->listing?->breed ?? 'Listing removed',
				'buyer_name' => 'You',
				'status' => ucfirst(str_replace('_', ' ', $item->status ?? 'pending')),
				'price' => $item->price,
				'created_at' => $item->created_at?->toDateTimeString(),
			]);
	}

	return Inertia::render('Dashboard', [
		'userRole' => $user->role,
		'stats' => [
			'listings_count' => $user->role === 'seller' ? $user->listings()->count() : 0,
			'cart_count' => $user->role === 'buyer' ? $user->carts()->count() : 0,
			'total_income' => $totalIncome,
			'registered_buyers' => $registeredBuyers,
			'items_sold' => $itemsSold,
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
		'insights' => [
			'recent_buyer_activity' => [
				'orders_last_7_days' => $ordersLast7Days,
				'unique_buyers_last_7_days' => $uniqueBuyersLast7Days,
			],
			'recent_orders' => $recentOrders,
		],
	]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Public listing browse page (guests, buyers, sellers)
Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');

Route::middleware('auth')->group(function () {
	// Profile Routes
	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

	Route::middleware('seller')->group(function () {
		Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
		Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
		Route::patch('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
		Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
	});

	// Cart Routes
	Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
	Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
	Route::delete('/cart/{cart}', [CartController::class, 'destroy'])->name('cart.destroy');

	// Messaging Routes
	Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
	Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
	Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
	Route::delete('/messages/conversation/{user}', [MessageController::class, 'deleteConversation'])->name('messages.deleteConversation');
	Route::post('/messages/{otherUserId}/read', [MessageController::class, 'markAsRead'])->name('messages.read');

	// Checkout/Purchases Routes
	Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
	Route::get('/my-purchases', [BuyerOrderController::class, 'index'])->name('buyer.orders.index');
	Route::post('/buyer/orders/{id}/received', [BuyerOrderController::class, 'updateStatus'])->name('buyer.orders.updateStatus');
	Route::delete('/my-purchases/{id}', [BuyerOrderController::class, 'destroy'])->name('buyer.orders.destroy');

	Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
	Route::post('/notifications/mark-selected', [NotificationController::class, 'markSelectedAsRead'])->name('notifications.markSelectedAsRead');
	Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroyAll');
	Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])
		->name('notifications.destroy');
	Route::post('/notifications/{id}/read', function ($id) { 
		$user = Auth::user();
		$user->notifications()->findOrFail($id)->markAsRead(); 
		return back(); 
	})->name('notifications.markAsRead');
    
});

// Seller Group
Route::middleware(['auth', 'seller'])->prefix('seller')->name('seller.')->group(function () {
	Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
	Route::post('/orders/{id}/status', [SellerOrderController::class, 'updateStatus'])->name('orders.updateStatus');
	Route::delete('/orders/{id}', [SellerOrderController::class, 'destroy'])->name('orders.destroy');
});

require __DIR__.'/auth.php';
