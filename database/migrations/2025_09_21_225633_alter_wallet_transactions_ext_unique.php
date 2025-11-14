<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // thêm cột nếu thiếu
            if (!Schema::hasColumn('wallet_transactions', 'ext_type')) {
                $table->string('ext_type')->nullable()->after('type');
            }
            if (!Schema::hasColumn('wallet_transactions', 'ext_id')) {
                $table->unsignedBigInteger('ext_id')->nullable()->after('ext_type');
            }
            if (!Schema::hasColumn('wallet_transactions', 'meta')) {
                $table->json('meta')->nullable()->after('ext_id');
            }

            // unique idempotent: 1 ngoại sinh → 1 giao dịch duy nhất
            $indexes = collect(Schema::getConnection()->select(
                "SHOW INDEX FROM wallet_transactions"
            ))->pluck('Key_name')->all();

            if (!in_array('wallet_tx_ext_unique', $indexes, true)) {
                $table->unique(['wallet_id', 'ext_type', 'ext_id'], 'wallet_tx_ext_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (in_array('wallet_tx_ext_unique', collect(
                Schema::getConnection()->select("SHOW INDEX FROM wallet_transactions")
            )->pluck('Key_name')->all(), true)) {
                $table->dropUnique('wallet_tx_ext_unique');
            }
            // không drop cột để tránh mất dữ liệu lịch sử
        });
    }
};
