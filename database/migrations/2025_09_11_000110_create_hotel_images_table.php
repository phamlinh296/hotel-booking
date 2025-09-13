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
        Schema::create('hotel_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete();          // Nếu xóa hotel thì xóa luôn ảnh
            $table->string('url');             // Link ảnh
            $table->unsignedSmallInteger('position')->default(0); // Thứ tự hiển thị
            $table->timestamps();

            $table->index(['hotel_id', 'position']); // Tăng tốc query lấy ảnh theo khách sạn
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_images');
    }
};
