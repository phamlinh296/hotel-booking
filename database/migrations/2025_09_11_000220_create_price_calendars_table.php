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
        Schema::create('price_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')
                  ->constrained('room_types')
                  ->cascadeOnDelete(); // Nếu xoá room type thì xoá luôn lịch giá

            $table->date('date');                        // Ngày áp dụng giá
            $table->decimal('price', 10, 2);             // Giá cho ngày đó
            $table->char('currency', 3)->default('VND'); // Đơn vị tiền tệ
            $table->timestamps();

            // Một room_type chỉ có 1 mức giá cho mỗi ngày
            $table->unique(['room_type_id', 'date']);

            // Index để query nhanh theo ngày và room_type
            $table->index(['date', 'room_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_calendars');
    }
};
