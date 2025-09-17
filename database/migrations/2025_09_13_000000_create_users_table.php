<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Họ tên
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name')->nullable(); // display_name

            // Đăng nhập
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Vai trò & trạng thái
            $table->enum('role', ['admin', 'author', 'user'])->default('user');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');

            // Thông tin cá nhân
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();

            // Profile
            $table->string('avatar')->nullable();
            $table->string('background_image')->nullable();
            $table->text('desc')->nullable();
            $table->string('job_title')->nullable();
            $table->string('href')->nullable();
            $table->integer('count')->default(0); // số lượng bài viết, lượt tương tác, v.v.
            // Hệ thống
            $table->rememberToken();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
