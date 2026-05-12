<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use App\Services\SmsService;
use Carbon\Carbon;
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

        // Fetch confirmed-and-unreminded appointments whose date falls in the
        // window. whereDate (not where=/whereIn) is required because the
        // 'date' cast on appointment_date binds the parameter as a datetime
        // string when using equality predicates, which never matches the
        // pure date stored in the DB column.
        $candidates = Appointment::query()
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereDate('appointment_date', '>=', $windowStart->toDateString())
            ->whereDate('appointment_date', '<=', $windowEnd->toDateString())
            ->with('user')
            ->get();

        // Narrow to the exact ±1h window in PHP. appointment_time is a TIME
        // column with a 'datetime:H:i' Carbon cast; format('H:i:s') gives a
        // clean time fragment we can safely concatenate with the date.
        $appointments = $candidates->filter(function (Appointment $a) use ($windowStart, $windowEnd) {
            $timeStr = $a->appointment_time instanceof Carbon
                ? $a->appointment_time->format('H:i:s')
                : (string) $a->appointment_time;

            $datetime = Carbon::parse(
                $a->appointment_date->format('Y-m-d').' '.$timeStr
            );

            return $datetime->greaterThanOrEqualTo($windowStart)
                && $datetime->lessThanOrEqualTo($windowEnd);
        });

        if ($appointments->isEmpty()) {
            $this->info("[reminders] no confirmed appointments in the next {$hours}h window");

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($appointments as $appointment) {
            $patient = $appointment->user;
            if (! $patient) {
                continue;
            }

            $timeStr = $appointment->appointment_time instanceof Carbon
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
