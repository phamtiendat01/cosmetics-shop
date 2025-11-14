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
        Schema::create('bot_intents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 'product_search', 'order_tracking', 'policy_inquiry', etc
            $table->string('display_name'); // Tên hiển thị
            $table->text('description')->nullable();
            $table->json('examples')->nullable(); // [example messages]
            $table->string('handler_class')->nullable(); // Class name xử lý intent này
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0); // Ưu tiên (số càng cao càng ưu tiên)
            $table->json('config')->nullable(); // Config riêng cho intent
            $table->timestamps();
            
            $table->index(['is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_intents');
    }
};
