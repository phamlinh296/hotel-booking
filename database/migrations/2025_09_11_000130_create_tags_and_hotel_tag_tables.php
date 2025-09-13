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
        // Bảng tags
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // Tên tag duy nhất (ví dụ: "Near Beach", "Family", "Business")
            $table->timestamps();
        });

        // Bảng pivot hotel_tag (N-N)
        Schema::create('hotel_tag', function (Blueprint $table) {
            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete();          // Xoá hotel -> xoá liên kết
            $table->foreignId('tag_id')
                  ->constrained('tags')
                  ->cascadeOnDelete();          // Xoá tag -> xoá liên kết
            $table->primary(['hotel_id', 'tag_id']); // Khóa chính composite để tránh trùng
            $table->timestamps();

            // Index phụ để query theo tag hoặc hotel nhanh hơn (tuỳ use case)
            $table->index(['tag_id', 'hotel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop pivot trước để tránh lỗi FK
        Schema::dropIfExists('hotel_tag');
        Schema::dropIfExists('tags');
    }
};
