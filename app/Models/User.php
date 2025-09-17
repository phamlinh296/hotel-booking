<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'name', //display_name
        'email',
        'password',
        'gender',
        'avatar',
        'background_image',
        'desc',
        'job_title',
        'href',
        'address',
        'role',             // user | author | admin
        'status',           // active | inactive | banned
        'date_of_birth',
        'phone',
        'count', // thêm
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
    ];

    // ================== Quan hệ ==================

    // 1 User (author) có thể đăng nhiều Hotel
    public function hotels()
    {
        return $this->hasMany(Hotel::class, 'created_by');
    }

    // 1 User có thể có nhiều booking
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // 1 User có thể có nhiều review
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // 1 User có nhiều thông báo
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // 1 User có nhiều payment (qua booking)
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // 1 User có nhiều bookmark
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // 1 User có nhiều lượt thích hotel
    public function hotelLikes()
    {
        return $this->hasMany(HotelLike::class);
    }

    // 1 User có nhiều lượt xem hotel
    public function hotelViews()
    {
        return $this->hasMany(HotelView::class);
    }

    // 1 User có nhiều recent view
    public function recentViews()
    {
        return $this->hasMany(RecentView::class);
    }
}
