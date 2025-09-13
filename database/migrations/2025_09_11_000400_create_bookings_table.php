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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)->unique(); // Mã booking (ticket code hiển thị cho user)

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete(); // User đặt phòng

            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete(); // Khách sạn được đặt

            $table->enum('status', ['pending','confirmed','cancelled','completed'])
                  ->default('pending')
                  ->index(); // Trạng thái booking

            $table->date('check_in')->index();  // Ngày nhận phòng
            $table->date('check_out');          // Ngày trả phòng
            $table->unsignedInteger('guests')->default(1); // Tổng số khách

            $table->char('currency', 3)->default('VND');   // Đơn vị tiền tệ
            $table->decimal('amount_subtotal', 10, 2)->default(0); // Tổng tiền trước giảm giá
            $table->decimal('amount_discount', 10, 2)->default(0); // Giảm giá
            $table->decimal('amount_total', 10, 2)->default(0);    // Tổng tiền sau giảm giá

            $table->text('notes')->nullable(); // Ghi chú thêm từ user

            $table->timestamps();

            // Index gộp để query nhanh lịch sử booking của user
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
