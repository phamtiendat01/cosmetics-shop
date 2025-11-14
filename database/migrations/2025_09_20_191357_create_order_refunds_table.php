<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_refunds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_return_id')->nullable()->constrained('order_returns')->nullOnDelete();
            $t->string('provider', 20)->nullable();       // COD|VIETQR|MOMO|VNPAY...
            $t->unsignedInteger('amount');                // VND
            $t->string('status', 20)->default('pending'); // pending|processed|failed
            $t->string('provider_ref')->nullable();       // mã refund của cổng
            $t->json('meta')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('order_refunds');
    }
};
