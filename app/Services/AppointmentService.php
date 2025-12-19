<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AppointmentService
{
    protected SlotGeneratorService $slotService;
    protected ClinicSetting $settings;

    public function __construct(SlotGeneratorService $slotService)
    {
        $this->slotService = $slotService;
        $this->settings = ClinicSetting::getInstance();
    }

    // ==================== Booking ====================

    public function book(User $patient, Carbon $datetime, ?string $notes = null): Appointment
    {
        $date = $datetime->copy()->startOfDay();
        $time = $datetime->format('H:i');

        // Validate booking
        $this->validateBooking($patient, $datetime);

        return Appointment::create([
            'user_id' => $patient->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $time,
            'status' => AppointmentStatus::PENDING,
            'notes' => $notes,
        ]);
    }

    protected function validateBooking(User $patient, Carbon $datetime): void
    {
        $date = $datetime->copy()->startOfDay();
        $time = $datetime->format('H:i');

        // Check if slot is available
        if (!$this->slotService->isSlotAvailable($datetime)) {
            throw new \InvalidArgumentException(__('هذا الموعد غير متاح'));
        }

        // Check if slot is already booked
        if (Appointment::isSlotBooked($date, $time)) {
            throw new \InvalidArgumentException(__('هذا الموعد محجوز بالفعل'));
        }

        // Check if patient has too many no-shows
        $noShowCount = Appointment::getNoShowCountForPatient($patient->id);
        if ($noShowCount >= 3) {
            throw new \InvalidArgumentException(__('لا يمكنك الحجز بسبب عدم الحضور المتكرر'));
        }

        // Check if patient already has an active appointment at this time
        $hasConflict = Appointment::forPatient($patient->id)
            ->forDate($date)
            ->whereTime('appointment_time', $time)
            ->active()
            ->exists();

        if ($hasConflict) {
            throw new \InvalidArgumentException(__('لديك حجز بالفعل في هذا الموعد'));
        }
    }

    public function canBook(User $patient, Carbon $datetime): array
    {
        try {
            $this->validateBooking($patient, $datetime);
            return ['can_book' => true, 'reason' => null];
        } catch (\InvalidArgumentException $e) {
            return ['can_book' => false, 'reason' => $e->getMessage()];
        }
    }

    // ==================== Status Management ====================

    public function confirm(Appointment $appointment): Appointment
    {
        if (!$appointment->isPending()) {
            throw new \InvalidArgumentException(__('لا يمكن تأكيد هذا الحجز'));
        }

        return $appointment->confirm();
    }

    public function complete(Appointment $appointment, ?string $adminNotes = null): Appointment
    {
        if (!$appointment->isConfirmed()) {
            throw new \InvalidArgumentException(__('لا يمكن إتمام هذا الحجز'));
        }

        return $appointment->complete($adminNotes);
    }

    public function cancel(Appointment $appointment, string $reason, CancelledBy $cancelledBy): Appointment
    {
        if (!$appointment->isActive()) {
            throw new \InvalidArgumentException(__('لا يمكن إلغاء هذا الحجز'));
        }

        return $appointment->cancel($reason, $cancelledBy);
    }

    public function markNoShow(Appointment $appointment): Appointment
    {
        if (!$appointment->isConfirmed()) {
            throw new \InvalidArgumentException(__('لا يمكن تسجيل عدم الحضور لهذا الحجز'));
        }

        return $appointment->markNoShow();
    }

    // ==================== Cancellation Validation ====================

    public function canCancel(Appointment $appointment, User $user): array
    {
        if (!$appointment->isActive()) {
            return ['can_cancel' => false, 'reason' => __('لا يمكن إلغاء هذا الحجز')];
        }

        if ($appointment->datetime->isPast()) {
            return ['can_cancel' => false, 'reason' => __('لا يمكن إلغاء موعد في الماضي')];
        }

        // If patient, check ownership
        if ($user->isPatient() && $appointment->user_id !== $user->id) {
            return ['can_cancel' => false, 'reason' => __('غير مصرح لك بإلغاء هذا الحجز')];
        }

        return ['can_cancel' => true, 'reason' => null];
    }

    // ==================== Queries ====================

    public function getPatientAppointments(User $patient, ?string $status = null): Collection
    {
        $query = Appointment::forPatient($patient->id)
            ->with('patient')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getPatientUpcomingAppointments(User $patient): Collection
    {
        return Appointment::forPatient($patient->id)
            ->upcoming()
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();
    }

    public function getAppointmentsForDate(Carbon $date): Collection
    {
        return Appointment::forDate($date)
            ->with('patient')
            ->orderBy('appointment_time')
            ->get();
    }

    public function getTodayAppointments(): Collection
    {
        return Appointment::today()
            ->with('patient')
            ->orderBy('appointment_time')
            ->get();
    }

    public function getUpcomingAppointments(?int $days = 7): Collection
    {
        $endDate = now()->addDays($days);

        return Appointment::active()
            ->whereBetween('appointment_date', [now()->toDateString(), $endDate->toDateString()])
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();
    }

    public function getAllAppointments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Appointment::with('patient');

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date
        if (!empty($filters['date'])) {
            $query->forDate($filters['date']);
        }

        // Filter by date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->betweenDates($filters['from_date'], $filters['to_date']);
        } elseif (!empty($filters['from_date'])) {
            $query->whereDate('appointment_date', '>=', $filters['from_date']);
        } elseif (!empty($filters['to_date'])) {
            $query->whereDate('appointment_date', '<=', $filters['to_date']);
        }

        // Filter by patient
        if (!empty($filters['patient_id'])) {
            $query->forPatient($filters['patient_id']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'appointment_date';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        if ($orderBy === 'appointment_date') {
            $query->orderBy('appointment_time', $orderDir);
        }

        return $query->paginate($perPage);
    }

    // ==================== Statistics ====================

    public function getStatistics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Appointment::query();

        if ($from && $to) {
            $query->betweenDates($from, $to);
        }

        $all = (clone $query)->count();
        $byStatus = [];

        foreach (AppointmentStatus::cases() as $status) {
            $byStatus[$status->value] = (clone $query)
                ->where('status', $status)
                ->count();
        }

        $todayQuery = Appointment::today();
        $today = [
            'total' => (clone $todayQuery)->count(),
            'pending' => (clone $todayQuery)->pending()->count(),
            'confirmed' => (clone $todayQuery)->confirmed()->count(),
            'completed' => (clone $todayQuery)->completed()->count(),
        ];

        $thisWeek = Appointment::thisWeek()->count();
        $thisMonth = Appointment::thisMonth()->count();

        return [
            'total' => $all,
            'by_status' => $byStatus,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
        ];
    }

    public function getDailyStatistics(Carbon $from, Carbon $to): Collection
    {
        $stats = collect();
        $current = $from->copy();

        while ($current->lte($to)) {
            $date = $current->toDateString();
            $dayAppointments = Appointment::forDate($current);

            $stats->push([
                'date' => $date,
                'day_name' => $current->locale('ar')->dayName,
                'total' => (clone $dayAppointments)->count(),
                'completed' => (clone $dayAppointments)->completed()->count(),
                'cancelled' => (clone $dayAppointments)->cancelled()->count(),
                'no_show' => (clone $dayAppointments)->noShow()->count(),
            ]);

            $current->addDay();
        }

        return $stats;
    }
}
