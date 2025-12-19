<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Dr. Admin',
            'email' => 'admin@clinic.com',
            'phone' => '01000000000',
            'password' => 'admin123',
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'phone_verified_at' => now(),
        ]);
    }
}
