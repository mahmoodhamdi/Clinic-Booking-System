<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_DEFAULT_PASSWORD', 'admin123');

        $admin = User::where('phone', '01000000000')->first();
        if (! $admin) {
            $admin = new User([
                'name' => 'Dr. Admin',
                'email' => 'admin@clinic.com',
                // Raw value; the User model's 'password' => 'hashed' cast
                // auto-hashes on save. DemoSeeder uses the same effective
                // result via explicit Hash::make() — both styles work because
                // the cast is hash-aware (skips already-hashed values).
                'password' => $password,
                'phone' => '01000000000',
                'phone_verified_at' => now(),
                // Force the doctor to set their own password before using the system.
                // Cleared by AuthController::changePassword() after a successful change.
                'must_change_password' => true,
            ]);
            $admin->role = UserRole::ADMIN;
            $admin->is_active = true;
            $admin->save();
        }

        $this->command->info('Admin user created/verified: phone=01000000000');
    }
}
