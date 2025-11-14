<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'payment_method')) {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE orders MODIFY payment_method VARCHAR(20) NOT NULL DEFAULT 'COD'"
            );
        } else {
            \Illuminate\Support\Facades\Schema::table('orders', function ($t) {
                $t->string('payment_method', 20)->default('COD');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('string', function (Blueprint $table) {
            //
        });
    }
};
