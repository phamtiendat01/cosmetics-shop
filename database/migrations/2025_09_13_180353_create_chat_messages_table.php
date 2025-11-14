<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chat_id');
            $table->enum('sender_type', ['customer', 'staff', 'system'])->index();
            $table->unsignedBigInteger('sender_id')->nullable()->index(); // users.id
            $table->string('type', 20)->default('text'); // text|image|file|event
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('chats')->cascadeOnDelete();
            $table->index(['chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
