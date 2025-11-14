<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $t) {
            $t->unsignedBigInteger('order_item_id')->nullable()->after('product_id');
            $t->boolean('verified_purchase')->default(false)->after('is_approved');
            $t->index('order_item_id');
            $t->unique(['user_id', 'order_item_id']); // 1 item/1 user -> 1 review
            $t->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $t) {
            $t->dropForeign(['order_item_id']);
            $t->dropUnique(['user_id', 'order_item_id']);
            $t->dropColumn(['order_item_id', 'verified_purchase']);
        });
    }
};
