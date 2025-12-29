<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $provider;

    protected ?string $apiKey;

    protected ?string $senderId;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'log');
        $this->apiKey = config('services.sms.api_key');
        $this->senderId = config('services.sms.sender_id');
    }

    /**
     * Send SMS message
     */
    public function send(string $phone, string $message): bool
    {
        return match ($this->provider) {
            'twilio' => $this->sendViaTwilio($phone, $message),
            'vonage' => $this->sendViaVonage($phone, $message),
            default => $this->logMessage($phone, $message),
        };
    }

    /**
     * Send SMS via Twilio
     */
    protected function sendViaTwilio(string $phone, string $message): bool
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = config('services.twilio.from');

            if (! $sid || ! $token || ! $from) {
                Log::warning('Twilio configuration is incomplete');

                return $this->logMessage($phone, $message);
            }

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                    [
                        'From' => $from,
                        'To' => $this->formatPhone($phone),
                        'Body' => $message,
                    ]
                );

            if ($response->successful()) {
                Log::info('SMS sent via Twilio', ['phone' => $this->maskPhone($phone)]);

                return true;
            }

            Log::error('Twilio SMS failed', [
                'phone' => $this->maskPhone($phone),
                'error' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Twilio SMS Error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Send SMS via Vonage (Nexmo)
     */
    protected function sendViaVonage(string $phone, string $message): bool
    {
        try {
            $key = config('services.vonage.key');
            $secret = config('services.vonage.secret');
            $from = config('services.vonage.from');

            if (! $key || ! $secret || ! $from) {
                Log::warning('Vonage configuration is incomplete');

                return $this->logMessage($phone, $message);
            }

            $response = Http::post('https://rest.nexmo.com/sms/json', [
                'api_key' => $key,
                'api_secret' => $secret,
                'from' => $from,
                'to' => $this->formatPhone($phone),
                'text' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['messages'][0]['status']) && $data['messages'][0]['status'] === '0') {
                    Log::info('SMS sent via Vonage', ['phone' => $this->maskPhone($phone)]);

                    return true;
                }
            }

            Log::error('Vonage SMS failed', [
                'phone' => $this->maskPhone($phone),
                'error' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Vonage SMS Error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Log message (for development/testing)
     */
    protected function logMessage(string $phone, string $message): bool
    {
        Log::info('SMS Message (logged)', [
            'to' => $this->maskPhone($phone),
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Format phone number for international format
     */
    protected function formatPhone(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // Add Egypt country code if not present
        if (str_starts_with($phone, '0')) {
            return '+2'.$phone;
        }

        if (! str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '20')) {
                return '+'.$phone;
            }

            return '+20'.$phone;
        }

        return $phone;
    }

    /**
     * Mask phone number for logging
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) < 6) {
            return '****';
        }

        return substr($phone, 0, 3).'****'.substr($phone, -3);
    }

    /**
     * Send OTP message
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        $message = __('auth.otp_message', ['otp' => $otp]);

        // Fallback message if translation not found
        if ($message === 'auth.otp_message') {
            $message = "رمز التحقق الخاص بك هو: {$otp}\nصالح لمدة 15 دقيقة.";
        }

        return $this->send($phone, $message);
    }

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder(string $phone, string $date, string $time): bool
    {
        $message = __('notifications.appointment_reminder_sms', [
            'date' => $date,
            'time' => $time,
        ]);

        // Fallback message
        if ($message === 'notifications.appointment_reminder_sms') {
            $message = "تذكير: لديك موعد في {$date} الساعة {$time}. نراك قريباً!";
        }

        return $this->send($phone, $message);
    }

    /**
     * Get current provider
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Check if using real SMS provider
     */
    public function isRealProvider(): bool
    {
        return in_array($this->provider, ['twilio', 'vonage']);
    }
}
