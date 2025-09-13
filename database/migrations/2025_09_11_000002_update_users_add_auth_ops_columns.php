<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Chuẩn Laravel cho xác thực email
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }

            // Kéo dài độ dài phone và có thể unique nếu bạn muốn login bằng phone
            $table->string('phone', 20)->nullable()->change();
            // $table->unique('phone'); // bật nếu muốn duy nhất

            // Trạng thái tài khoản
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'suspended'])->default('active')->after('password')->index();
            }

            // Theo dõi đăng nhập gần nhất
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 64)->default('Asia/Ho_Chi_Minh')->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'preferred_currency')) {
                $table->char('preferred_currency', 3)->default('VND')->after('timezone');
            }
            if (!Schema::hasColumn('users', 'preferred_locale')) {
                $table->string('preferred_locale', 10)->nullable()->after('preferred_currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Gỡ lần lượt các cột thêm vào (đừng drop các cột bạn đã có sẵn)
            $table->dropColumn([
                'email_verified_at',
                'status',
                'last_login_at',
                'timezone',
                'preferred_currency',
                'preferred_locale',
            ]);
            // Nếu bạn đã bật unique cho phone thì cần dropIndex trước:
            // $table->dropUnique(['phone']);
        });
    }
};
