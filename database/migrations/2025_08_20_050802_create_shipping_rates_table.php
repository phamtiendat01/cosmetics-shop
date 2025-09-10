<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('carrier_id')->constrained('shipping_carriers')->cascadeOnDelete();
            $t->foreignId('zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete(); // null = áp dụng toàn quốc
            $t->string('name')->nullable();        // "Chuẩn", "Nhanh", ...
            // Điều kiện áp dụng
            $t->decimal('min_weight', 10, 3)->nullable(); // kg
            $t->decimal('max_weight', 10, 3)->nullable();
            $t->unsignedBigInteger('min_total')->nullable(); // VND
            $t->unsignedBigInteger('max_total')->nullable();
            // Công thức tính
            $t->unsignedBigInteger('base_fee')->default(0); // phí cơ bản
            $t->unsignedBigInteger('per_kg_fee')->default(0); // cộng thêm mỗi kg (làm tròn lên)
            $t->unsignedTinyInteger('etd_min_days')->nullable();
            $t->unsignedTinyInteger('etd_max_days')->nullable();
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
