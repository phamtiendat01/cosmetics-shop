<?php
<<<<<<< HEAD

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        // MySQL/MariaDB: tạo fulltext nếu chưa có
        DB::statement("ALTER TABLE products ADD FULLTEXT ft_products_name (name)");
        DB::statement("ALTER TABLE products ADD FULLTEXT ft_products_desc (short_desc, description)");
    }
    public function down(): void
    {
        // tuỳ phiên bản MySQL, có thể cần DROP INDEX theo tên
=======
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // nếu cột chưa có thì thêm vào
            if (!Schema::hasColumn('products', 'short_desc')) {
                $table->string('short_desc')->nullable();
            }
        });

        // thêm fulltext index
        DB::statement("ALTER TABLE products ADD FULLTEXT ft_products_name (name)");
        DB::statement("ALTER TABLE products ADD FULLTEXT ft_products_desc (short_desc, description)");
    }

    public function down(): void
    {
        // xoá fulltext index
        DB::statement("ALTER TABLE products DROP INDEX ft_products_name");
        DB::statement("ALTER TABLE products DROP INDEX ft_products_desc");

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'short_desc')) {
                $table->dropColumn('short_desc');
            }
        });
>>>>>>> 26951689d1eb166ac6660244f4404972363ff21b
    }
};
