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
        Schema::create('user_tiers', function (Blueprint $t) {
            $t->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $t->foreignId('tier_id')->constrained('member_tiers');
            $t->timestamp('qualified_at')->nullable(); // lúc đạt hạng
            $t->timestamp('expires_at')->nullable();   // hết hạng (31/12 năm sau)
            $t->unsignedInteger('current_year_spend')->default(0); // chi tiêu năm hiện tại
            $t->timestamp('last_evaluated_at')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tiers');
    }
};
