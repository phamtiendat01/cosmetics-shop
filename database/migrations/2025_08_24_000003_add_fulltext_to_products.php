<?php

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
    }
};
