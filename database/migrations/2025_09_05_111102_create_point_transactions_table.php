<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->integer('delta');                       // +earn / -burn
            $t->string('type', 20);                    // earn | burn | adjust
            $t->string('status', 20)->default('pending'); // pending|confirmed|cancelled
            $t->nullableMorphs('reference');           // App\Models\Order, Review,...
            $t->json('meta')->nullable();
            $t->timestamp('available_at')->nullable(); // thời điểm mở khoá
            $t->timestamp('expires_at')->nullable();   // thời điểm hết hạn
            $t->timestamps();
            $t->index(['user_id', 'status', 'available_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
