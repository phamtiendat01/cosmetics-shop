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
        Schema::create('bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('bot_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content'); // Nội dung tin nhắn
            $table->string('intent')->nullable()->index(); // Intent được detect
            $table->decimal('confidence', 3, 2)->nullable(); // Confidence score (0.00 - 1.00)
            $table->json('tools_used')->nullable(); // [{tool, params, result}]
            $table->json('metadata')->nullable(); // Extra data
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['conversation_id', 'created_at']);
            $table->index(['intent', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_messages');
    }
};
