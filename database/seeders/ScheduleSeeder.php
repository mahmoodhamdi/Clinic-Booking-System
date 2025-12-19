<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Sunday to Thursday: 9 AM - 5 PM with lunch break
        $workDays = [
            DayOfWeek::SUNDAY,
            DayOfWeek::MONDAY,
            DayOfWeek::TUESDAY,
            DayOfWeek::WEDNESDAY,
            DayOfWeek::THURSDAY,
        ];

        foreach ($workDays as $day) {
            Schedule::create([
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
                'break_start' => '13:00',
                'break_end' => '14:00',
            ]);
        }

        // Friday: Morning only
        Schedule::create([
            'day_of_week' => DayOfWeek::FRIDAY,
            'start_time' => '10:00',
            'end_time' => '14:00',
            'is_active' => false, // Closed by default
        ]);

        // Saturday: Closed
        Schedule::create([
            'day_of_week' => DayOfWeek::SATURDAY,
            'start_time' => '09:00',
            'end_time' => '14:00',
            'is_active' => false,
        ]);
    }
}
