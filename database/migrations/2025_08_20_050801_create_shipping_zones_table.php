<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $t) {
            $t->id();
            $t->string('name');                 // "Nội thành HN", "TP.HCM", "Tỉnh lân cận"...
            $t->json('province_codes')->nullable(); // mảng mã tỉnh (VD: ["HN","HCM","HP"])
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
