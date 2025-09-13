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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->cascadeOnDelete(); // Nếu xoá booking -> xoá luôn payment

            $table->foreignId('method_id')
                  ->constrained('payment_methods')
                  ->cascadeOnDelete(); // Phương thức thanh toán

            $table->enum('status', [
                'initiated',   // khởi tạo
                'authorized',  // đã xác thực
                'captured',    // đã trừ tiền thành công
                'failed',      // thất bại
                'refunded'     // đã hoàn tiền
            ])->default('initiated')->index();

            $table->decimal('amount', 10, 2);          // Số tiền thanh toán
            $table->char('currency', 3)->default('VND'); // Đơn vị tiền tệ

            $table->string('provider_txn_id')->nullable()->index(); // Mã giao dịch từ cổng thanh toán
            $table->json('raw_payload')->nullable();   // Lưu dữ liệu raw trả về từ PSP (VNPay, Stripe, v.v.)

            $table->timestamps();

            // Index gộp để query nhanh theo booking + trạng thái
            $table->index(['booking_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
