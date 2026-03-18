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
        $password = env('ADMIN_DEFAULT_PASSWORD', 'admin123');

        $admin = User::where('phone', '01000000000')->first();
        if (! $admin) {
            $admin = new User([
                'name' => 'Dr. Admin',
                'email' => 'admin@clinic.com',
                'password' => $password,
                'phone' => '01000000000',
                'phone_verified_at' => now(),
            ]);
            $admin->role = UserRole::ADMIN;
            $admin->is_active = true;
            $admin->save();
        }

        $this->command->info('Admin user created/verified: phone=01000000000');
    }
}
