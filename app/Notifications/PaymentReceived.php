<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'title' => 'تأكيد الدفع',
            'message' => sprintf(
                'تم استلام مبلغ %s بنجاح',
                $this->payment->formatted_total
            ),
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->total,
            'formatted_amount' => $this->payment->formatted_total,
            'appointment_id' => $this->payment->appointment_id,
        ];
    }
}
