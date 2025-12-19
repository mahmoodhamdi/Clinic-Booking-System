<?php

namespace Database\Seeders;

use App\Models\ClinicSetting;
use Illuminate\Database\Seeder;

class ClinicSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (ClinicSetting::count() === 0) {
            ClinicSetting::create([
                'clinic_name' => 'عيادة الشفاء',
                'doctor_name' => 'د. أحمد محمد',
                'specialization' => 'طب عام',
                'phone' => '01012345678',
                'email' => 'clinic@example.com',
                'address' => 'شارع التحرير، القاهرة',
                'slot_duration' => 30,
                'max_patients_per_slot' => 1,
                'advance_booking_days' => 30,
                'cancellation_hours' => 24,
            ]);
            $this->command->info('Clinic settings created.');
        } else {
            $this->command->info('Clinic settings already exist, skipping.');
        }
    }
}
