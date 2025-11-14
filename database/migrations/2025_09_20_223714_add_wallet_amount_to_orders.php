<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->bigInteger('wallet_amount')->default(0)->after('discount_total');
        });
    }
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn('wallet_amount');
        });
    }
};
