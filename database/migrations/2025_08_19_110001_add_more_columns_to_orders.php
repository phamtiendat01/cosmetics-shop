<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('customer_email')->nullable()->after('customer_phone');
            $t->string('shipping_method')->nullable()->after('shipping_address');
            $t->string('tracking_no')->nullable()->after('shipping_method');
            $t->json('tags')->nullable()->after('tracking_no');
            $t->text('notes')->nullable()->after('tags');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn(['customer_email', 'shipping_method', 'tracking_no', 'tags', 'notes']);
        });
    }
};
