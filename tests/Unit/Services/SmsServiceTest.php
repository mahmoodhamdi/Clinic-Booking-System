<?php

namespace Tests\Unit\Services;

use App\Services\SmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    // ==================== send / dispatch ====================

    /** @test */
    public function default_log_provider_writes_to_log_and_returns_true(): void
    {
        config()->set('services.sms.provider', 'log');
        Log::spy();

        $service = new SmsService;
        $result = $service->send('01012345678', 'hello');

        $this->assertTrue($result);
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return $message === 'SMS Message (logged)'
                && isset($context['to'])
                && str_contains($context['to'], '****');
        })->once();
    }

    /** @test */
    public function unknown_provider_falls_back_to_log(): void
    {
        config()->set('services.sms.provider', 'totally-fake');

        $service = new SmsService;
        $this->assertTrue($service->send('01012345678', 'hello'));
    }

    /** @test */
    public function send_otp_uses_the_otp_template(): void
    {
        config()->set('services.sms.provider', 'log');
        Log::spy();

        (new SmsService)->sendOtp('01012345678', '123456');

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return $message === 'SMS Message (logged)'
                && (str_contains((string) ($context['message'] ?? ''), '123456')
                    || $context['message'] === '[REDACTED]');
        })->once();
    }

    /** @test */
    public function send_appointment_reminder_uses_the_reminder_template(): void
    {
        config()->set('services.sms.provider', 'log');
        Log::spy();

        (new SmsService)->sendAppointmentReminder('01012345678', '2026-05-08', '10:00');

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            $msg = (string) ($context['message'] ?? '');

            return $message === 'SMS Message (logged)'
                && ($context['message'] === '[REDACTED]'
                    || (str_contains($msg, '2026-05-08') && str_contains($msg, '10:00')));
        })->once();
    }

    // ==================== Twilio branch ====================

    /** @test */
    public function twilio_provider_posts_to_messages_endpoint_with_basic_auth(): void
    {
        config()->set('services.sms.provider', 'twilio');
        config()->set('services.twilio.sid', 'AC123');
        config()->set('services.twilio.token', 'secret');
        config()->set('services.twilio.from', '+15551234567');

        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $result = (new SmsService)->send('01012345678', 'hello');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/2010-04-01/Accounts/AC123/Messages.json')
                && $request['From'] === '+15551234567'
                && $request['To'] === '+201012345678'
                && $request['Body'] === 'hello';
        });
    }

    /** @test */
    public function twilio_provider_falls_back_to_log_when_credentials_missing(): void
    {
        config()->set('services.sms.provider', 'twilio');
        config()->set('services.twilio.sid', null);
        config()->set('services.twilio.token', null);
        config()->set('services.twilio.from', null);
        Http::fake();
        Log::spy();

        $result = (new SmsService)->send('01012345678', 'hello');

        $this->assertTrue($result);
        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg) => str_contains((string) $msg, 'Twilio'))->once();
    }

    /** @test */
    public function twilio_provider_returns_false_on_http_failure(): void
    {
        config()->set('services.sms.provider', 'twilio');
        config()->set('services.twilio.sid', 'AC123');
        config()->set('services.twilio.token', 'secret');
        config()->set('services.twilio.from', '+15551234567');

        Http::fake([
            'api.twilio.com/*' => Http::response(['error' => 'auth_failed'], 401),
        ]);

        $this->assertFalse((new SmsService)->send('01012345678', 'hello'));
    }

    // ==================== Vonage branch ====================

    /** @test */
    public function vonage_provider_posts_to_rest_nexmo_with_credentials(): void
    {
        config()->set('services.sms.provider', 'vonage');
        config()->set('services.vonage.key', 'KEY1');
        config()->set('services.vonage.secret', 'SECRET1');
        config()->set('services.vonage.from', 'Clinic');

        Http::fake([
            'rest.nexmo.com/sms/json' => Http::response([
                'messages' => [['status' => '0']],
            ], 200),
        ]);

        $result = (new SmsService)->send('01012345678', 'hello');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'rest.nexmo.com/sms/json')
                && $request['api_key'] === 'KEY1'
                && $request['api_secret'] === 'SECRET1'
                && $request['from'] === 'Clinic'
                && $request['to'] === '+201012345678';
        });
    }

    /** @test */
    public function vonage_provider_returns_false_when_provider_reports_non_zero_status(): void
    {
        config()->set('services.sms.provider', 'vonage');
        config()->set('services.vonage.key', 'KEY1');
        config()->set('services.vonage.secret', 'SECRET1');
        config()->set('services.vonage.from', 'Clinic');

        Http::fake([
            'rest.nexmo.com/sms/json' => Http::response([
                'messages' => [['status' => '4', 'error-text' => 'invalid number']],
            ], 200),
        ]);

        $this->assertFalse((new SmsService)->send('01012345678', 'hello'));
    }

    /** @test */
    public function vonage_provider_falls_back_to_log_when_credentials_missing(): void
    {
        config()->set('services.sms.provider', 'vonage');
        config()->set('services.vonage.key', null);
        config()->set('services.vonage.secret', null);
        config()->set('services.vonage.from', null);
        Http::fake();
        Log::spy();

        $result = (new SmsService)->send('01012345678', 'hello');

        $this->assertTrue($result);
        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg) => str_contains((string) $msg, 'Vonage'))->once();
    }

    // ==================== Phone formatting ====================

    /** @test */
    public function phone_starting_with_zero_gets_egypt_country_code(): void
    {
        config()->set('services.sms.provider', 'twilio');
        config()->set('services.twilio.sid', 'AC123');
        config()->set('services.twilio.token', 's');
        config()->set('services.twilio.from', 'F');
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM'], 201)]);

        (new SmsService)->send('01012345678', 'msg');

        Http::assertSent(fn ($r) => $r['To'] === '+201012345678');
    }

    /** @test */
    public function phone_already_starting_with_20_gets_plus_only(): void
    {
        config()->set('services.sms.provider', 'twilio');
        config()->set('services.twilio.sid', 'AC123');
        config()->set('services.twilio.token', 's');
        config()->set('services.twilio.from', 'F');
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM'], 201)]);

        (new SmsService)->send('201012345678', 'msg');

        Http::assertSent(fn ($r) => $r['To'] === '+201012345678');
    }

    /** @test */
    public function phone_with_plus_prefix_passes_through(): void
    {
        config()->set('services.sms.provider', 'twilio');
        config()->set('services.twilio.sid', 'AC123');
        config()->set('services.twilio.token', 's');
        config()->set('services.twilio.from', 'F');
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM'], 201)]);

        (new SmsService)->send('+15551234567', 'msg');

        Http::assertSent(fn ($r) => $r['To'] === '+15551234567');
    }

    // ==================== Provider introspection ====================

    /** @test */
    public function get_provider_returns_configured_provider(): void
    {
        config()->set('services.sms.provider', 'vonage');
        $this->assertSame('vonage', (new SmsService)->getProvider());

        config()->set('services.sms.provider', 'log');
        $this->assertSame('log', (new SmsService)->getProvider());
    }

    /** @test */
    public function is_real_provider_only_returns_true_for_twilio_or_vonage(): void
    {
        config()->set('services.sms.provider', 'log');
        $this->assertFalse((new SmsService)->isRealProvider());

        config()->set('services.sms.provider', 'twilio');
        $this->assertTrue((new SmsService)->isRealProvider());

        config()->set('services.sms.provider', 'vonage');
        $this->assertTrue((new SmsService)->isRealProvider());
    }
}
