<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('spin_logs', function (Blueprint $t) {
            if (!Schema::hasColumn('spin_logs', 'saved_at')) {
                $t->timestamp('saved_at')->nullable()->after('coupon_code');
                $t->index('saved_at');
            }
        });
    }
    public function down(): void
    {
        Schema::table('spin_logs', function (Blueprint $t) {
            if (Schema::hasColumn('spin_logs', 'saved_at')) {
                $t->dropIndex(['saved_at']);
                $t->dropColumn('saved_at');
            }
        });
    }
};
