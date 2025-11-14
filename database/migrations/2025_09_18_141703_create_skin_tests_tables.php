<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('skin_tests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('session_id', 64)->nullable();
            $t->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $t->string('dominant_skin_type')->nullable(); // oily/dry/combination/sensitive
            $t->json('metrics_json')->nullable();
            $t->json('recommendation_json')->nullable();
            $t->string('failed_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('skin_test_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('skin_test_id')->constrained()->cascadeOnDelete();
            $t->string('path'); // storage path (public hoặc s3 key)
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->boolean('face_ok')->default(true);
            $t->timestamps();
        });

        // optional: consent log
        Schema::create('skin_consent_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('skin_test_id')->constrained()->cascadeOnDelete();
            $t->timestamp('consent_at');
            $t->string('policy_version')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skin_consent_logs');
        Schema::dropIfExists('skin_test_photos');
        Schema::dropIfExists('skin_tests');
    }
};
