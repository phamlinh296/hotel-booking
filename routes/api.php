<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TagController;
use App\Mail\BookingRefundMail;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/profile', [AuthController::class, 'profile']);
    // Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Me (profile)
Route::get('/me/profile', [UserController::class, 'getProfile'])->middleware('auth:sanctum');
Route::post('/me/update-profile', [UserController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::get('/me/likes', [UserController::class, 'myLikes'])->middleware('auth:sanctum');
Route::get('/me/bookmarks', [UserController::class, 'myBookmarks'])->middleware('auth:sanctum');
Route::get('/me/recent-views', [HotelController::class, 'recentViews'])->middleware('auth:sanctum');

Route::prefix('hotels')->group(function () {
    //search
    Route::get('/search', [HotelController::class, 'search']);
    // Recent viewed hotels - phải đặt trước /{id} để không bị nhầm
    // Route::get('/recent', [HotelController::class, 'recentViews'])->middleware('auth:sanctum');

    //crud
    Route::get('/', [HotelController::class, 'index']);                 // Public - List hotels
    Route::get('/{id}', [HotelController::class, 'show'])->middleware('auth:sanctum');           // Public - Hotel detail
    Route::post('/', [HotelController::class, 'store'])->middleware('auth:sanctum', 'role:admin,author');              // Host/Admin - Create hotel
    Route::put('/{id}', [HotelController::class, 'update'])->middleware('auth:sanctum', 'role:admin,author');          // Host/Admin - Update hotel
    Route::delete('/{id}', [HotelController::class, 'destroy'])->middleware('auth:sanctum', 'role:admin,author');       // Host/Admin - Delete hotel
    Route::post('/{id}/images', [HotelController::class, 'uploadImages'])->middleware('auth:sanctum', 'role:admin,author'); // Host/Admin - Upload images

    // Reviews
    Route::get('/{id}/reviews', [HotelController::class, 'reviews'])->middleware('auth:sanctum');
    Route::put('/reviews/{id}', [HotelController::class, 'updateReview'])->middleware('auth:sanctum');
    Route::post('/{id}/reviews', [HotelController::class, 'addReview'])->middleware('auth:sanctum');
    Route::delete('/reviews/{id}', [HotelController::class, 'deleteReview'])->middleware('auth:sanctum');

    // Bookmarks
    Route::post('/{id}/bookmarks', [HotelController::class, 'addBookmark'])->middleware('auth:sanctum');
    Route::delete('/{id}/bookmarks', [HotelController::class, 'removeBookmark'])->middleware('auth:sanctum');

    // Likes
    Route::post('/{id}/likes', [HotelController::class, 'addLike'])->middleware('auth:sanctum');
    Route::delete('/{id}/likes', [HotelController::class, 'removeLike'])->middleware('auth:sanctum');

    // Rooms
    //get room hotel
    Route::get('/{hotel}/rooms', [RoomController::class, 'index']); // Public
    Route::post('/{hotel}/rooms', [RoomController::class, 'store'])->middleware('auth:sanctum', 'role:admin,author'); // Host/Admin


});

// Room update/delete by ID
Route::prefix('rooms')->group(function () {
    Route::put('/{room}', [RoomController::class, 'update'])->middleware('auth:sanctum', 'role:admin,author'); // Host/Admin
    Route::delete('/{room}', [RoomController::class, 'destroy'])->middleware('auth:sanctum', 'role:admin,author'); // Host/Admin
});

// Booking
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']); // Customer
    Route::get('/bookings', [BookingController::class, 'index']);  // Customer
    Route::get('/bookings/{id}', [BookingController::class, 'show']); // Customer
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']); // Customer

    Route::get('/host/bookings', [BookingController::class, 'hostBookings'])->middleware('role:author'); // Host
    Route::get('/admin/bookings', [BookingController::class, 'adminBookings'])->middleware('role:admin'); // Admin
    //còn curl nữa, luồng thanh toán
    Route::put('/bookings/{id}/customer-info', [BookingController::class, 'updateCustomerInfo']);
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm'])->middleware('role:author');; // Host
    Route::post('/bookings/{id}/reject', [BookingController::class, 'reject'])->middleware('role:author');; // Host
    Route::post('/bookings/{id}/payment-status', [BookingController::class, 'updatePaymentStatus']);

    // Payment
    Route::post('/payments', [PaymentController::class, 'store']); // Customer
    Route::get('/payments/{id}', [PaymentController::class, 'show']); // Customer/Admin
});

// Categories
# Lấy categories
Route::get('/categories', [CategoryController::class, 'index']);
# Tạo categories -admin
Route::post('/categories', [CategoryController::class, 'store'])->middleware('auth:sanctum', 'role:admin');

// Tags
# Lấy tags
Route::get('/tags', [TagController::class, 'index']);
# Tạo tag (Admin)
Route::post('/tags', [TagController::class, 'store'])->middleware('auth:sanctum', 'role:admin');
# Gắn tag vào hotel (Host/Admin)
Route::post('/hotels/{id}/tags', [TagController::class, 'attachToHotel'])->middleware('auth:sanctum', 'role:admin,author');

// Notifications
//lấy noti
Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth:sanctum');
//đánh dấu đã đọc
Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->middleware('auth:sanctum');
Route::post('/notifications', [NotificationController::class, 'store'])->middleware('auth:sanctum');

//test mail
Route::get('/test-mail', function () {
    $booking = Booking::first();
    if (!$booking) {
        return "No booking found in database. Please create one first.";
    }
    Mail::to('your_email@example.com')->send(new BookingRefundMail($booking));
    return "Mail sent!";
});
