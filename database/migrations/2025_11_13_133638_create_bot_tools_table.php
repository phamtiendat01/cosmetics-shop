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
        Schema::create('bot_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 'searchProducts', 'getOrderStatus', etc
            $table->string('display_name'); // Tên hiển thị
            $table->text('description'); // Mô tả tool
            $table->json('parameters_schema'); // JSON schema cho parameters
            $table->string('handler_class'); // Class name xử lý tool này
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // Config riêng cho tool
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_tools');
    }
};
