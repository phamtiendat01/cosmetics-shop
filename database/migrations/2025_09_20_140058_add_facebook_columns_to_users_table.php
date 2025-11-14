<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'facebook_id')) {
                $table->string('facebook_id')->nullable()->index()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'facebook_email')) {
                $table->string('facebook_email')->nullable()->after('facebook_id');
            }
            if (!Schema::hasColumn('users', 'facebook_avatar')) {
                $table->string('facebook_avatar')->nullable()->after('facebook_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'facebook_id')) $table->dropColumn('facebook_id');
            if (Schema::hasColumn('users', 'facebook_email')) $table->dropColumn('facebook_email');
            if (Schema::hasColumn('users', 'facebook_avatar')) $table->dropColumn('facebook_avatar');
        });
    }
};
