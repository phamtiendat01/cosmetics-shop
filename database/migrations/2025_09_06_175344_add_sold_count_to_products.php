<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (!Schema::hasColumn('products', 'sold_count')) {
                $t->unsignedBigInteger('sold_count')->default(0)->after('has_variants');
            }
        });
    }
    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (Schema::hasColumn('products', 'sold_count')) {
                $t->dropColumn('sold_count');
            }
        });
    }
};
