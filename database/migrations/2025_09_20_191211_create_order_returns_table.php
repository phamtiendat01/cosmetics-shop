<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status', 20)->default('requested'); // requested|approved|rejected|in_transit|received|refunded|cancelled
            $t->string('reason')->nullable();               // lí do khách
            $t->string('refund_method', 20)->nullable();    // original|manual|store_credit
            $t->unsignedInteger('expected_refund')->default(0); // tạm tính (VND)
            $t->unsignedInteger('final_refund')->default(0);    // chốt
            $t->json('meta')->nullable(); // hướng dẫn, ảnh kèm, pickup info...
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('order_returns');
    }
};
