<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['customer', 'staff'])->index();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('chats')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['chat_id', 'user_id']);
            $table->index('last_read_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
