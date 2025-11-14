<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Cần Laravel 10+ có cast 'password' => 'hashed' trong User
        $u = User::firstOrCreate(
            ['email' => 'admin@cosme.local'],
            ['name' => 'Super Admin', 'password' => 'admin123', 'is_active' => true]
        );

        if (!$u->hasRole('super-admin')) {
            $u->assignRole('super-admin');
        }
    }
}
