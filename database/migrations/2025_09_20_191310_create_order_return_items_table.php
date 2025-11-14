<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_return_id')->constrained('order_returns')->cascadeOnDelete();
            $t->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $t->unsignedInteger('qty');                 // SL khách muốn trả
            $t->unsignedInteger('approved_qty')->nullable(); // SL kho chấp nhận
            $t->string('condition', 20)->nullable();    // resell|damaged
            $t->unsignedInteger('line_refund')->default(0); // số tiền hoàn cho dòng
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('order_return_items');
    }
};
