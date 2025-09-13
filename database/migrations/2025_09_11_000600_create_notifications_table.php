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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete(); // Nếu xoá user -> xoá luôn thông báo

            $table->string('title');             // Tiêu đề thông báo
            $table->text('body')->nullable();    // Nội dung chi tiết (nếu có)
            $table->boolean('read')->default(false)->index(); // Đã đọc / chưa đọc

            $table->timestamps();

            // Index để lấy nhanh tất cả thông báo của 1 user
            $table->index(['user_id', 'read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
