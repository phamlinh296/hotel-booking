<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_tags');
    }
}
