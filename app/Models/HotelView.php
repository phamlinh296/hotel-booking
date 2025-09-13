<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelView extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'viewed_at'
    ];

    public $timestamps = false; // nếu chỉ lưu datetime

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
