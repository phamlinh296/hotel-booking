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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Tên khách sạn
            $table->string('slug')->unique();             // Slug duy nhất (SEO)
            $table->text('description')->nullable();      // Mô tả
            $table->string('location_city')->index();     // Thành phố
            $table->string('location_country')->index();  // Quốc gia
            $table->string('address')->nullable();        // Địa chỉ chi tiết
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('thumbnail')->nullable();      // Ảnh đại diện
            $table->float('rating_avg')->default(0);      // Điểm trung bình
            $table->unsignedInteger('rating_count')->default(0); // Tổng số review
            $table->decimal('price_from', 10, 2)->default(0);     // Giá thấp nhất
            $table->timestamps();
            $table->softDeletes(); // để xoá mềm nếu cần
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
