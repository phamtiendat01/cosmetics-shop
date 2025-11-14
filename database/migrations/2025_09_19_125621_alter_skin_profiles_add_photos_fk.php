<?php
// database/migrations/2024_09_19_120000_alter_skin_profiles_add_photos_fk.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('skin_profiles', function (Blueprint $t) {
            // snapshot ảnh (lưu tối đa 3 path)
            $t->json('photos_json')->nullable()->after('routine_json');

            // nếu trước đó chỉ tạo index, thêm FK (không đổi kiểu cột)
            $t->foreign('skin_test_id')
                ->references('id')->on('skin_tests')
                ->nullOnDelete(); // xoá skin_test => set null
        });
    }
    public function down(): void
    {
        Schema::table('skin_profiles', function (Blueprint $t) {
            $t->dropForeign(['skin_test_id']);
            $t->dropColumn('photos_json');
        });
    }
};
