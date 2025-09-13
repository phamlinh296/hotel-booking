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
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->cascadeOnDelete(); // Xoá booking -> xoá luôn các item

            $table->foreignId('room_type_id')
                  ->constrained('room_types')
                  ->cascadeOnDelete(); // Xoá room type -> xoá item liên quan

            $table->unsignedInteger('qty_rooms')->default(1);     // Số phòng của loại này
            $table->unsignedTinyInteger('adults')->default(1);    // Số người lớn
            $table->unsignedTinyInteger('children')->default(0);  // Số trẻ em

            // Tổng tiền cấp dòng (trước & sau giảm giá)
            $table->decimal('line_subtotal', 10, 2)->default(0);
            $table->decimal('line_discount', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);

            $table->timestamps();

            // Tối ưu truy vấn lấy item theo booking
            $table->index(['booking_id', 'room_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
