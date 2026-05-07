<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            ClinicSettingSeeder::class,
            ScheduleSeeder::class,
        ]);

        if ($this->shouldSeedDemoData()) {
            $this->call([DemoSeeder::class]);
        } else {
            $this->command->warn('Skipping DemoSeeder (production environment). Set SEED_DEMO_DATA=true to override.');
        }
    }

    // Demo data (fake patients, appointments, prescriptions) seeds automatically
    // in local/testing. In production it must be opted-in via SEED_DEMO_DATA=true
    // to prevent fake records ending up in real clinics' databases.
    private function shouldSeedDemoData(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        return filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN);
    }
}
