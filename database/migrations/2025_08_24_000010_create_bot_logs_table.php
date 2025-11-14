<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_logs', function (Blueprint $t) {
            $t->id();
            $t->string('session_id')->index();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->text('message');
            $t->text('reply')->nullable();
            $t->string('handled_by', 40)->nullable();
            $t->string('intent', 40)->nullable();
            $t->string('matched_slug')->nullable();
            $t->unsignedSmallInteger('product_count')->default(0);
            $t->unsignedInteger('latency_ms')->default(0);
            $t->boolean('ok')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('bot_logs');
    }
};
