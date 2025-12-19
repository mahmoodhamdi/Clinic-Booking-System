<?php

namespace Tests\Unit\Enums;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    /** @test */
    public function has_correct_values(): void
    {
        $this->assertEquals('pending', AppointmentStatus::PENDING->value);
        $this->assertEquals('confirmed', AppointmentStatus::CONFIRMED->value);
        $this->assertEquals('completed', AppointmentStatus::COMPLETED->value);
        $this->assertEquals('cancelled', AppointmentStatus::CANCELLED->value);
        $this->assertEquals('no_show', AppointmentStatus::NO_SHOW->value);
    }

    /** @test */
    public function has_english_labels(): void
    {
        $this->assertEquals('Pending', AppointmentStatus::PENDING->label());
        $this->assertEquals('Confirmed', AppointmentStatus::CONFIRMED->label());
        $this->assertEquals('Completed', AppointmentStatus::COMPLETED->label());
        $this->assertEquals('Cancelled', AppointmentStatus::CANCELLED->label());
        $this->assertEquals('No Show', AppointmentStatus::NO_SHOW->label());
    }

    /** @test */
    public function has_arabic_labels(): void
    {
        $this->assertEquals('في الانتظار', AppointmentStatus::PENDING->labelAr());
        $this->assertEquals('مؤكد', AppointmentStatus::CONFIRMED->labelAr());
        $this->assertEquals('مكتمل', AppointmentStatus::COMPLETED->labelAr());
        $this->assertEquals('ملغي', AppointmentStatus::CANCELLED->labelAr());
        $this->assertEquals('لم يحضر', AppointmentStatus::NO_SHOW->labelAr());
    }

    /** @test */
    public function has_colors(): void
    {
        $this->assertEquals('warning', AppointmentStatus::PENDING->color());
        $this->assertEquals('info', AppointmentStatus::CONFIRMED->color());
        $this->assertEquals('success', AppointmentStatus::COMPLETED->color());
        $this->assertEquals('danger', AppointmentStatus::CANCELLED->color());
        $this->assertEquals('secondary', AppointmentStatus::NO_SHOW->color());
    }

    /** @test */
    public function identifies_active_statuses(): void
    {
        $this->assertTrue(AppointmentStatus::PENDING->isActive());
        $this->assertTrue(AppointmentStatus::CONFIRMED->isActive());
        $this->assertFalse(AppointmentStatus::COMPLETED->isActive());
        $this->assertFalse(AppointmentStatus::CANCELLED->isActive());
        $this->assertFalse(AppointmentStatus::NO_SHOW->isActive());
    }

    /** @test */
    public function identifies_final_statuses(): void
    {
        $this->assertFalse(AppointmentStatus::PENDING->isFinal());
        $this->assertFalse(AppointmentStatus::CONFIRMED->isFinal());
        $this->assertTrue(AppointmentStatus::COMPLETED->isFinal());
        $this->assertTrue(AppointmentStatus::CANCELLED->isFinal());
        $this->assertTrue(AppointmentStatus::NO_SHOW->isFinal());
    }

    /** @test */
    public function returns_active_statuses_array(): void
    {
        $active = AppointmentStatus::activeStatuses();

        $this->assertCount(2, $active);
        $this->assertContains(AppointmentStatus::PENDING, $active);
        $this->assertContains(AppointmentStatus::CONFIRMED, $active);
    }

    /** @test */
    public function returns_final_statuses_array(): void
    {
        $final = AppointmentStatus::finalStatuses();

        $this->assertCount(3, $final);
        $this->assertContains(AppointmentStatus::COMPLETED, $final);
        $this->assertContains(AppointmentStatus::CANCELLED, $final);
        $this->assertContains(AppointmentStatus::NO_SHOW, $final);
    }
}
