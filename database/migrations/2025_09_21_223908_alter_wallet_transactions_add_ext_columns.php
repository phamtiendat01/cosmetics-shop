<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('wallet_transactions')) return;

        Schema::table('wallet_transactions', function (Blueprint $t) {
            if (!Schema::hasColumn('wallet_transactions', 'ext_type')) {
                $t->string('ext_type', 50)->nullable()->index();
            }
            if (!Schema::hasColumn('wallet_transactions', 'ext_id')) {
                $t->unsignedBigInteger('ext_id')->nullable()->index();
            }
            if (!Schema::hasColumn('wallet_transactions', 'meta')) {
                $t->json('meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('wallet_transactions')) return;

        Schema::table('wallet_transactions', function (Blueprint $t) {
            if (Schema::hasColumn('wallet_transactions', 'meta')) $t->dropColumn('meta');
            if (Schema::hasColumn('wallet_transactions', 'ext_id')) $t->dropColumn('ext_id');
            if (Schema::hasColumn('wallet_transactions', 'ext_type')) $t->dropColumn('ext_type');
        });
    }
};
