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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete(); // Nếu xoá user thì xoá luôn review

            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete(); // Nếu xoá hotel thì xoá luôn review

            $table->unsignedTinyInteger('rating');   // Điểm đánh giá 1..5
            $table->string('title')->nullable();     // Tiêu đề review
            $table->text('comment')->nullable();     // Nội dung chi tiết

            $table->timestamps();

            // Tăng tốc query lấy review theo khách sạn, sắp xếp theo thời gian
            $table->index(['hotel_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
