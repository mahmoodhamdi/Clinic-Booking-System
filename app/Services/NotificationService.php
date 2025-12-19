<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentReminder;
use App\Notifications\PaymentReceived;
use App\Notifications\PrescriptionReady;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function sendAppointmentConfirmed(Appointment $appointment): void
    {
        $appointment->patient->notify(new AppointmentConfirmed($appointment));
    }

    public function sendAppointmentReminder(Appointment $appointment): void
    {
        $appointment->patient->notify(new AppointmentReminder($appointment));
    }

    public function sendAppointmentCancelled(
        Appointment $appointment,
        string $cancelledBy,
        ?string $reason = null
    ): void {
        $appointment->patient->notify(
            new AppointmentCancelled($appointment, $cancelledBy, $reason)
        );
    }

    public function sendPrescriptionReady(Prescription $prescription): void
    {
        $patient = $prescription->medicalRecord?->patient;

        if ($patient) {
            $patient->notify(new PrescriptionReady($prescription));
        }
    }

    public function sendPaymentReceived(Payment $payment): void
    {
        $patient = $payment->appointment?->patient;

        if ($patient) {
            $patient->notify(new PaymentReceived($payment));
        }
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function deleteNotification(DatabaseNotification $notification): void
    {
        $notification->delete();
    }

    public function getNotifications(User $user, int $limit = 15)
    {
        return $user->notifications()->paginate($limit);
    }

    public function getUnreadNotifications(User $user)
    {
        return $user->unreadNotifications;
    }

    public function sendAppointmentReminders(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $appointments = Appointment::with('patient')
            ->whereDate('appointment_date', $tomorrow)
            ->active()
            ->get();

        $count = 0;
        foreach ($appointments as $appointment) {
            $this->sendAppointmentReminder($appointment);
            $count++;
        }

        return $count;
    }
}
