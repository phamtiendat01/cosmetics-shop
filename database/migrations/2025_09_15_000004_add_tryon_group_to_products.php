<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (!Schema::hasColumn('products', 'tryon_group')) {
                $t->string('tryon_group', 24)->nullable()->after('concerns'); // ví dụ: 'lipstick'
                $t->index('tryon_group');
            }
        });
    }
    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (Schema::hasColumn('products', 'tryon_group')) $t->dropColumn('tryon_group');
        });
    }
};
