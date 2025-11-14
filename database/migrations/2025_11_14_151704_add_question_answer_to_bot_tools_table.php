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
        Schema::table('bot_tools', function (Blueprint $table) {
            // Câu hỏi hiển thị cho user
            $table->string('question')->nullable()->after('display_name');
            
            // Câu trả lời khi user chọn câu hỏi này
            $table->text('answer')->nullable()->after('question');
            
            // Phân loại (VD: 'shipping', 'return', 'product', 'payment', 'general')
            $table->string('category', 50)->nullable()->after('answer');
            
            // Thứ tự hiển thị
            $table->integer('order')->default(0)->after('category');
            
            // Icon/emoji cho câu hỏi (tùy chọn)
            $table->string('icon', 20)->nullable()->after('order');
            
            $table->index(['category', 'is_active']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_tools', function (Blueprint $table) {
            $table->dropIndex(['category', 'is_active']);
            $table->dropIndex(['order']);
            $table->dropColumn(['question', 'answer', 'category', 'order', 'icon']);
        });
    }
};
