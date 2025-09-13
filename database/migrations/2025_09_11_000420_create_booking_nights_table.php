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
        Schema::create('booking_nights', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_item_id')
                  ->constrained('booking_items')
                  ->cascadeOnDelete(); // Xoá item -> xoá các đêm tương ứng

            $table->date('date');                        // Đêm này (ngày check-in của đêm)
            $table->decimal('price_per_room', 10, 2);    // Giá 1 phòng cho đêm đó
            $table->unsignedInteger('qty_rooms')->default(1); // Số phòng áp dụng trong đêm
            $table->decimal('line_total', 10, 2);        // = price_per_room * qty_rooms

            $table->timestamps();

            // Mỗi booking_item chỉ có 1 bản ghi cho một ngày
            $table->unique(['booking_item_id', 'date']);

            // Tối ưu truy vấn theo item & ngày
            $table->index(['date', 'booking_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_nights');
    }
};
