<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_vouchers', 'description')) {
                $table->string('description', 255)->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_vouchers', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
