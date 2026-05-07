<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use App\Services\SmsService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders
        {--hours=24 : Hours ahead to look for upcoming confirmed appointments}
        {--dry-run : List what would be sent without actually sending}';

    protected $description = 'Send a one-time reminder to patients with confirmed appointments coming up. Idempotent via reminder_sent_at.';

    public function handle(SmsService $sms): int
    {
        $hours = (int) $this->option('hours');
        $dry = (bool) $this->option('dry-run');

        // Window: appointments scheduled between H-1 and H+1 hours from now
        // (a 2h window absorbs hourly cron drift while the reminder_sent_at
        // guard prevents double-sends within that window).
        $windowStart = now()->addHours($hours - 1);
        $windowEnd = now()->addHours($hours + 1);

        // Pre-filter by date in SQL (driver-agnostic), then narrow to the
        // exact 2h window in PHP. The whereBetween covers the at-most-two
        // calendar dates that intersect the window.
        $candidates = Appointment::query()
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereBetween('appointment_date', [
                $windowStart->toDateString(),
                $windowEnd->toDateString(),
            ])
            ->with('patient')
            ->get();

        $appointments = $candidates->filter(function (Appointment $a) use ($windowStart, $windowEnd) {
            // appointment_time is cast to Carbon ('datetime:H:i'); take just
            // the time portion so it pairs with the date cleanly.
            $timeStr = $a->appointment_time instanceof \Carbon\Carbon
                ? $a->appointment_time->format('H:i:s')
                : (string) $a->appointment_time;
            $datetime = \Carbon\Carbon::parse(
                $a->appointment_date->format('Y-m-d').' '.$timeStr
            );

            return $datetime->between($windowStart, $windowEnd);
        });

        if ($appointments->isEmpty()) {
            $this->info("[reminders] no confirmed appointments in the next {$hours}h window");

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;
            if (! $patient) {
                continue;
            }

            $timeStr = $appointment->appointment_time instanceof \Carbon\Carbon
                ? $appointment->appointment_time->format('H:i')
                : (string) $appointment->appointment_time;

            $this->line(sprintf(
                '%s appointment #%d for %s @ %s %s',
                $dry ? '[DRY]' : '[SEND]',
                $appointment->id,
                $patient->name,
                $appointment->appointment_date->format('Y-m-d'),
                $timeStr
            ));

            if ($dry) {
                continue;
            }

            // 1. In-app + email (if enabled): goes through standard notification stack
            $patient->notify(new AppointmentReminder($appointment));

            // 2. SMS via configured provider. Falls back to log-only if SMS_PROVIDER=log.
            if ($patient->phone) {
                $sms->sendAppointmentReminder(
                    $patient->phone,
                    $appointment->appointment_date->format('Y-m-d'),
                    $timeStr
                );
            }

            // Mark sent only after dispatching. If notify() throws the row stays
            // unmarked and the next cron tick retries.
            $appointment->update(['reminder_sent_at' => now()]);
            $sent++;
        }

        $verb = $dry ? 'would be sent' : 'sent';
        $this->info("[reminders] {$sent} reminder(s) {$verb}");

        return self::SUCCESS;
    }
}
