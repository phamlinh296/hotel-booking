<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'listing_category_id',
        'title',
        'slug',
        'description',
        'address',
        'location_city',
        'location_country',
        'latitude',
        'longitude',
        'featured_image',
        'view_count',
        'rating_avg',
        'rating_count',
        'price_from',
        'price_text',
        'max_guests',
        'bedrooms',
        'bathrooms',
        'sale_off',
        'is_ads',
    ];

    // ===== Quan hệ =====
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function listingCategory()
    {
        return $this->belongsTo(ListingCategory::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function galleries()
    {
        return $this->hasMany(HotelImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function likes()
    {
        return $this->hasMany(HotelLike::class);
    }

    public function views()
    {
        return $this->hasMany(HotelView::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'hotel_tags');
    }
}
