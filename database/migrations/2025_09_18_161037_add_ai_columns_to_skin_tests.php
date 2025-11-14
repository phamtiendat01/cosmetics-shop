<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('skin_tests', function (\Illuminate\Database\Schema\Blueprint $t) {
            if (!Schema::hasColumn('skin_tests', 'status'))              $t->string('status')->default('new')->index();
            if (!Schema::hasColumn('skin_tests', 'budget'))              $t->unsignedInteger('budget')->nullable();
            if (!Schema::hasColumn('skin_tests', 'metrics_json'))        $t->json('metrics_json')->nullable();
            if (!Schema::hasColumn('skin_tests', 'recommendation_json')) $t->json('recommendation_json')->nullable();
            if (!Schema::hasColumn('skin_tests', 'dominant_skin_type'))  $t->string('dominant_skin_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skin_tests', function (Blueprint $table) {
            //
        });
    }
};
