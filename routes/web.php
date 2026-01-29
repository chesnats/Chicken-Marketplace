<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\CartController;   
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
	return Inertia::render('Welcome', [
		'canResetPassword' => Route::has('password.request'),
		'status' => session('status'),
	]);
})->name('home');

Route::get('/dashboard', function () {
	$user = Auth::user();

	if (!$user) {
		return redirect()->route('login');
	}
    
	return Inertia::render('Dashboard', [
		'userRole' => $user->role,
		'stats' => [
			'listings_count' => $user->role === 'seller' ? $user->listings()->count() : 0,
			'cart_count' => $user->role === 'buyer' ? $user->carts()->count() : 0,
		]
	]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
	// Profile Routes
	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

	Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
	Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
	Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');

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

	Route::patch('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
	Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
    
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
Route::middleware(['auth'])->prefix('seller')->name('seller.')->group(function () {
	Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
	Route::post('/orders/{id}/status', [SellerOrderController::class, 'updateStatus'])->name('orders.updateStatus');
	Route::delete('/orders/{id}', [SellerOrderController::class, 'destroy'])->name('orders.destroy');
});

require __DIR__.'/auth.php';
