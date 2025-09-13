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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete(); // Nếu xoá user thì xoá bookmark

            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete(); // Nếu xoá hotel thì xoá bookmark

            $table->timestamps();

            // Khóa chính kép để tránh trùng bookmark
            $table->primary(['user_id', 'hotel_id']);

            // Index phụ để tìm khách sạn user đã bookmark nhanh hơn
            $table->index(['hotel_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
