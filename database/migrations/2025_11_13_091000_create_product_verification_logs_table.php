<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')
                ->nullable()
                ->constrained('product_qr_codes')
                ->nullOnDelete();

            // Verification details
            $table->string('qr_code', 255)->nullable();
            $table->enum('verification_result', [
                'authentic',
                'fake',
                'suspicious'
            ]);

            $table->string('verifier_ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Additional info
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('qr_code_id');
            $table->index('verification_result');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_verification_logs');
    }
};
