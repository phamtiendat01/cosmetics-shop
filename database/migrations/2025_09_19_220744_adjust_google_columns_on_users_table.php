<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Bổ sung google_id nếu chưa có
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_id')) {
                // index/unique tuỳ bạn; unique thường hợp lý
                $table->string('google_id', 191)->nullable()->unique('users_google_id_unique');
            }
        });

        // 2) Thêm google_email, google_avatar (đặt vị trí hợp lý)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_email')) {
                if (Schema::hasColumn('users', 'google_id')) {
                    $table->string('google_email', 191)->nullable()->after('google_id');
                } else {
                    $table->string('google_email', 191)->nullable()->after('email');
                }
            }

            if (!Schema::hasColumn('users', 'google_avatar')) {
                if (Schema::hasColumn('users', 'google_email')) {
                    $table->string('google_avatar', 255)->nullable()->after('google_email');
                } else {
                    $table->string('google_avatar', 255)->nullable()->after('email');
                }
            }

            // email_verified_at thường đã có; nếu chưa thì thêm
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Gỡ unique nếu đã tạo
            try {
                $table->dropUnique('users_google_id_unique');
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('users', 'google_avatar')) {
                $table->dropColumn('google_avatar');
            }
            if (Schema::hasColumn('users', 'google_email')) {
                $table->dropColumn('google_email');
            }
            if (Schema::hasColumn('users', 'google_id')) {
                $table->dropColumn('google_id');
            }
            // Không động vào email_verified_at nếu bạn còn dùng
        });
    }
};
