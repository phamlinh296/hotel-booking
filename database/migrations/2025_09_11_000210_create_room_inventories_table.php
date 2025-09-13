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
        Schema::create('room_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')
                  ->constrained('room_types')
                  ->cascadeOnDelete();  // Nếu xoá room type thì xoá luôn inventory

            $table->date('date');                   // Ngày lưu trú
            $table->unsignedInteger('rooms_total'); // Tổng số phòng khả dụng
            $table->unsignedInteger('rooms_sold')->default(0); // Số phòng đã bán
            $table->timestamps();

            // Một room_type chỉ có 1 record tồn kho cho mỗi ngày
            $table->unique(['room_type_id', 'date']);

            // Index để query nhanh khi check availability
            $table->index(['date', 'room_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_inventories');
    }
};
