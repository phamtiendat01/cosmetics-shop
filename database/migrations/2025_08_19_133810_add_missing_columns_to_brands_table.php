<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // 1) Thêm cột nếu thiếu
        Schema::table('brands', function (Blueprint $t) {
            if (!Schema::hasColumn('brands', 'slug')) {
                // thêm tạm thời cho phép null để backfill trước
                $t->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('brands', 'logo')) {
                $t->string('logo')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('brands', 'website')) {
                $t->string('website')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('brands', 'sort_order')) {
                $t->unsignedInteger('sort_order')->default(0)->after('website');
            }
            if (!Schema::hasColumn('brands', 'is_active')) {
                $t->boolean('is_active')->default(true)->after('sort_order');
            }
        });

        // 2) Backfill slug duy nhất cho các dòng cũ
        if (Schema::hasColumn('brands', 'slug')) {
            $hasIndex = collect(DB::select("SHOW INDEX FROM `brands` WHERE Key_name = 'brands_slug_unique'"))->isNotEmpty();
            if (!$hasIndex) {
                Schema::table('brands', function (Blueprint $t) {
                    $t->unique('slug', 'brands_slug_unique');
                });
            }
        }
    }
};
