<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->foreignId('certificate_id')
                ->constrained('product_blockchain_certificates')
                ->cascadeOnDelete();
            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained('order_items')
                ->nullOnDelete();

            // QR Code data
            $table->string('qr_code', 255)->unique();
            $table->string('qr_image_path', 500)->nullable();
            $table->string('qr_image_url', 500)->nullable();

            // Verification tracking
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by', 255)->nullable(); // IP address
            $table->integer('verification_count')->default(0);

            // Fraud detection
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason', 500)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('product_variant_id');
            $table->index('certificate_id');
            $table->index('order_item_id');
            $table->index('qr_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_qr_codes');
    }
};
