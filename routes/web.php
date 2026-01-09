<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\CartController;   
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    
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

    // 🐔 CHICKEN MARKETPLACE ROUTES
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

    // Checkout/Purchases Routes
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/my-purchases', [BuyerOrderController::class, 'index'])->name('buyer.orders.index');
    Route::post('/buyer/orders/{id}/received', [BuyerOrderController::class, 'updateStatus'])->name('buyer.orders.updateStatus');
    Route::delete('/my-purchases/{id}', [BuyerOrderController::class, 'destroy'])->name('buyer.orders.destroy');

    // 🔔 NOTIFICATION ROUTES (FIXED)
    Route::post('/notifications/{id}/read', function ($id) { 
        auth()->user()->notifications()->findOrFail($id)->markAsRead(); 
        return back(); 
    })->name('notifications.markAsRead');

    Route::delete('/notifications/{id}', function ($id) {
        auth()->user()->notifications()->where('id', $id)->delete();
        return back();
    })->name('notifications.destroy');
});

// Seller Group
Route::middleware(['auth'])->prefix('seller')->name('seller.')->group(function () {
    Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{id}/status', [SellerOrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::delete('/orders/{id}', [SellerOrderController::class, 'destroy'])->name('orders.destroy');
});

require __DIR__.'/auth.php';