<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_carriers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();        // ghn, ghtk, vtpost, jtexpress...
            $t->string('logo')->nullable();      // URL/đường dẫn logo
            $t->boolean('supports_cod')->default(true);
            $t->boolean('enabled')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->json('config')->nullable();      // chỗ để API key nếu sau này kết nối
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('shipping_carriers');
    }
};
