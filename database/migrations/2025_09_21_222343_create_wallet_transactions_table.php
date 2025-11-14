<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            // credit / debit / hold / release / refund / payment / adjust
            $t->string('type', 20);
            $t->bigInteger('amount');             // số tiền thay đổi (+/-)
            $t->bigInteger('balance_after');      // số dư sau giao dịch
            $t->string('reference_type')->nullable();  // 'order','order_return','admin',...
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['reference_type', 'reference_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
