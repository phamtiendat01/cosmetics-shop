<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'edited_at')) $table->timestamp('edited_at')->nullable()->after('updated_at');
            if (!Schema::hasColumn('reviews', 'deleted_at')) $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'edited_at')) $table->dropColumn('edited_at');
            if (Schema::hasColumn('reviews', 'deleted_at')) $table->dropSoftDeletes();
        });
    }
};
