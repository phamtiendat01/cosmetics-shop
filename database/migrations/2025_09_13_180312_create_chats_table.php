<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id')->nullable();     // users.id nếu đã đăng nhập
            $table->string('visitor_session_id', 255)->nullable()->index(); // khách chưa đăng nhập
            $table->unsignedBigInteger('assigned_to')->nullable()->index(); // agent (users.id)
            $table->string('source', 32)->default('site')->index();     // site|facebook|zalo|...
            $table->unsignedBigInteger('product_id')->nullable();       // ngữ cảnh tuỳ bạn
            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('status', ['open', 'pending', 'closed'])->default('open')->index();
            $table->string('public_token', 64)->nullable()->unique();   // cho kênh công khai
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();

            $table->index('product_id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
