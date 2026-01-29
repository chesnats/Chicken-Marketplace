<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\ListingApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\CheckoutController;

Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token
    ]);
});

    // Listings handled by controller below; named routes added for Ziggy compatibility.
        
    // Public user endpoints
    Route::get('/users/{id}/listings', [UserApiController::class, 'listings']);
    Route::get('/users/{id}', [UserApiController::class, 'show']);
    Route::get('/users', [UserApiController::class, 'index']);

    // Listings (API) - public reads
    Route::get('/listings', [ListingApiController::class, 'index']);
    Route::get('/listings/{listing}', [ListingApiController::class, 'show']);

    // Protected write routes
    Route::middleware('auth:sanctum')->group(function () {
        // Orders overview for authenticated users (buyer/seller)
        Route::get('/orders', [OrderApiController::class, 'index']);
        // Listings (write)
        Route::post('/listings', [ListingApiController::class, 'store']);
        Route::patch('/listings/{listing}', [ListingApiController::class, 'update']);
        Route::delete('/listings/{listing}', [ListingApiController::class, 'destroy']);
        // Also allow managing listings via the user nested route for convenience
        Route::patch('/users/{user}/listings/{listing}', [ListingApiController::class, 'update']);
        Route::delete('/users/{user}/listings/{listing}', [ListingApiController::class, 'destroy']);

        // Cart
        Route::get('/cart', [CartApiController::class, 'index']);
        Route::post('/cart', [CartApiController::class, 'store']);
        Route::delete('/cart/{cart}', [CartApiController::class, 'destroy']);

        // Messages
        Route::get('/messages', [MessageApiController::class, 'index']);
        Route::post('/messages', [MessageApiController::class, 'store']);
        Route::delete('/messages/{message}', [MessageApiController::class, 'destroy']);

        // Checkout / Orders
        Route::get('/my-purchases', [OrderApiController::class, 'index']);
        Route::post('/buyer/orders/{id}/received', [OrderApiController::class, 'updateStatus']);
        Route::delete('/my-purchases/{id}', [OrderApiController::class, 'destroy']);

        // Seller routes
        Route::prefix('seller')->group(function () {
            Route::get('/orders', [OrderApiController::class, 'index']);
            Route::post('/orders/{id}/status', [OrderApiController::class, 'updateStatus']);
            Route::delete('/orders/{id}', [OrderApiController::class, 'destroy']);
        });
    });



