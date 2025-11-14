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
        Schema::create('bot_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained('bot_conversations')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('bot_messages')->nullOnDelete();
            $table->string('event_type'); // 'intent_detected', 'tool_called', 'user_satisfaction', 'conversion', etc
            $table->json('data')->nullable(); // Event data
            $table->string('session_id', 100)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['event_type', 'created_at']);
            $table->index(['conversation_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_analytics');
    }
};
