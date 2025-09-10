<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_coupons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $t->string('source')->default('spin');
            $t->timestamp('saved_at')->nullable();
            $t->timestamps();

            $t->unique(['user_id', 'coupon_id']);
            $t->index(['user_id', 'saved_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
    }
};
