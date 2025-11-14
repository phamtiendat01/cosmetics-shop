<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }
        // Nếu cần, có thể bổ sung kiểm tra & thêm cột còn thiếu ở đây
        // ví dụ:
        // if (!Schema::hasColumn('settings','value')) {
        //     Schema::table('settings', fn(Blueprint $t) => $t->longText('value')->nullable());
        // }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
