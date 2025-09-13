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
        Schema::create('debit_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete(); // Xoá user -> xoá luôn thẻ

            $table->string('brand')->nullable();       // Loại thẻ: Visa, MasterCard, JCB...
            $table->string('card_holder');             // Tên chủ thẻ
            $table->char('card_last4', 4);             // 4 số cuối của thẻ
            $table->unsignedTinyInteger('exp_month');  // Tháng hết hạn
            $table->unsignedSmallInteger('exp_year');  // Năm hết hạn

            $table->string('token')->nullable();       // Token từ PSP (Stripe, VNPay, MoMo...)

            $table->timestamps();

            // Index để tránh 1 user lưu trùng 2 lần cùng 1 thẻ
            $table->unique(['user_id', 'card_last4', 'exp_month', 'exp_year'], 'user_card_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_cards');
    }
};
