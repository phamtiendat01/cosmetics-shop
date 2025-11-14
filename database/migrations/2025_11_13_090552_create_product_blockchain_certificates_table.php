<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_blockchain_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            // Certificate data
            $table->string('certificate_hash', 255)->unique();

            // IPFS storage
            $table->string('ipfs_hash', 255)->nullable();
            $table->string('ipfs_url', 500)->nullable();

            // Metadata (JSON)
            $table->json('metadata');

            // Timestamps
            $table->timestamp('minted_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('product_variant_id');
            $table->index('certificate_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_blockchain_certificates');
    }
};
