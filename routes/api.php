<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;

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

    // Rooms
    //get room hotel
    Route::get('/{hotel}/rooms', [RoomController::class, 'index']); // Public
    Route::post('/{hotel}/rooms', [RoomController::class, 'store'])->middleware('auth:sanctum', 'role:admin,host'); // Host/Admin
});

// Room update/delete by ID
Route::prefix('rooms')->group(function () {
    Route::put('/{room}', [RoomController::class, 'update'])->middleware('auth:sanctum', 'role:admin,host'); // Host/Admin
    Route::delete('/{room}', [RoomController::class, 'destroy'])->middleware('auth:sanctum', 'role:admin,host'); // Host/Admin
});

// Booking
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']); // Customer
    Route::get('/bookings', [BookingController::class, 'index']);  // Customer
    Route::get('/bookings/{id}', [BookingController::class, 'show']); // Customer
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']); // Customer

    Route::get('/host/bookings', [BookingController::class, 'hostBookings'])->middleware('role:host'); // Host
    Route::get('/admin/bookings', [BookingController::class, 'adminBookings'])->middleware('role:admin'); // Admin

    // Payment
    Route::post('/payments', [PaymentController::class, 'store']); // Customer
    Route::get('/payments/{id}', [PaymentController::class, 'show']); // Customer/Admin
});
