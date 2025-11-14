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
        Schema::create('bot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index(); // Session ID hoặc unique identifier
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // User đã đăng nhập
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->json('metadata')->nullable(); // {skin_types, concerns, budget, name, etc}
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('completed_at')->nullable();
            
            $table->index(['session_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_conversations');
    }
};
