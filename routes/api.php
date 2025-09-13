<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('hotels')->group(function () {
    // Recent viewed hotels - phải đặt trước /{id} để không bị nhầm
    Route::get('/recent', [HotelController::class, 'recentViews'])->middleware('auth:sanctum');

    //crud
    Route::get('/', [HotelController::class, 'index']);                 // Public - List hotels
    Route::get('/{id}', [HotelController::class, 'show'])->middleware('auth:sanctum');           // Public - Hotel detail
    Route::post('/', [HotelController::class, 'store'])->middleware('auth:sanctum', 'role:admin,host');              // Host/Admin - Create hotel
    Route::put('/{id}', [HotelController::class, 'update'])->middleware('auth:sanctum', 'role:admin,host');          // Host/Admin - Update hotel
    Route::delete('/{id}', [HotelController::class, 'destroy'])->middleware('auth:sanctum', 'role:admin,host');       // Host/Admin - Delete hotel
    Route::post('/{id}/images', [HotelController::class, 'uploadImages'])->middleware('auth:sanctum', 'role:admin,host'); // Host/Admin - Upload images

    // Reviews
    Route::get('/{id}/reviews', [HotelController::class, 'reviews'])->middleware('auth:sanctum');
    Route::post('/{id}/reviews', [HotelController::class, 'addReview'])->middleware('auth:sanctum');

    // Bookmarks
    Route::put('/{id}/bookmarks', [HotelController::class, 'addBookmark'])->middleware('auth:sanctum');
    Route::delete('/{id}/bookmarks', [HotelController::class, 'removeBookmark'])->middleware('auth:sanctum');

    // Likes
    Route::put('/{id}/likes', [HotelController::class, 'addLike'])->middleware('auth:sanctum');
    Route::delete('/{id}/likes', [HotelController::class, 'removeLike'])->middleware('auth:sanctum');
});
