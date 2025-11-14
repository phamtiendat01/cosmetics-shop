<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_chain_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->foreignId('certificate_id')
                ->nullable()
                ->constrained('product_blockchain_certificates')
                ->nullOnDelete();

            // Movement details
            $table->enum('movement_type', [
                'manufacture',
                'warehouse_in',
                'warehouse_out',
                'sale',
                'return',
                'recall'
            ]);

            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();

            // Link to order if sale
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained('order_items')
                ->nullOnDelete();

            // Batch info
            $table->string('batch_number', 255)->nullable();
            $table->integer('quantity')->default(1);

            // Timestamps
            $table->timestamp('moved_at');
            $table->timestamps();

            // Indexes
            $table->index('product_variant_id');
            $table->index('certificate_id');
            $table->index('order_id');
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_chain_movements');
    }
};
