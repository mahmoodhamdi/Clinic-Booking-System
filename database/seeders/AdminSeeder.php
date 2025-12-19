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
        User::firstOrCreate(
            ['phone' => '01000000000'],
            [
                'name' => 'Dr. Admin',
                'email' => 'admin@clinic.com',
                'password' => 'admin123',
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'phone_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created/verified: phone=01000000000, password=admin123');
    }
}
