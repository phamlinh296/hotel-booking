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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete(); // Nếu xóa hotel thì xóa luôn room types

            $table->string('name');                   // Tên loại phòng (Deluxe, Suite, Standard...)
            $table->unsignedTinyInteger('capacity_adults');          // Số lượng người lớn tối đa
            $table->unsignedTinyInteger('capacity_children')->default(0); // Số trẻ em tối đa
            $table->string('bed_type')->nullable();   // Loại giường (King, Queen, Twin...)
            $table->json('amenities_json')->nullable(); // Tiện ích riêng của phòng (nếu có)
            $table->decimal('base_price', 10, 2);     // Giá cơ bản (nếu không có price calendar)

            $table->timestamps();

            // Một khách sạn không được có 2 loại phòng trùng tên
            $table->unique(['hotel_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
