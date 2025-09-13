<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bảng amenities: danh mục tiện ích (Wifi, Pool, Gym, ...)
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã ngắn duy nhất, ví dụ: WIFI, POOL, GYM
            $table->string('name');           // Tên hiển thị
            $table->timestamps();
        });

        // Bảng pivot hotel_amenity: liên kết N-N giữa hotels và amenities
        Schema::create('hotel_amenity', function (Blueprint $table) {
            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete(); // Xoá hotel -> xoá liên kết
            $table->foreignId('amenity_id')
                  ->constrained('amenities')
                  ->cascadeOnDelete(); // Xoá amenity -> xoá liên kết

            // Khóa chính composite để đảm bảo 1 tiện ích không gán trùng cho cùng 1 khách sạn
            $table->primary(['hotel_id', 'amenity_id']);

            // Index phụ tuỳ hướng truy vấn (lấy nhanh danh sách hotel theo amenity)
            $table->index(['amenity_id', 'hotel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_amenity');
        Schema::dropIfExists('amenities');
    }
};
