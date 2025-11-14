<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('skin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('skin_test_id')->nullable(); // tham chiếu test đã lưu
            $table->string('dominant_skin_type', 32)->nullable();   // oily/dry/combination/sensitive
            $table->json('metrics_json')->nullable();               // {oiliness, dryness, redness, acne_score}
            $table->json('routine_json')->nullable();               // routine đề xuất
            $table->string('note', 190)->nullable();
            $table->timestamps();

            $table->index('skin_test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skin_profiles');
    }
};
