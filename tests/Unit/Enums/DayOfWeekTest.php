<?php

namespace Tests\Unit\Enums;

use App\Enums\DayOfWeek;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DayOfWeekTest extends TestCase
{
    /** @test */
    public function enum_has_correct_values(): void
    {
        $this->assertEquals(0, DayOfWeek::SUNDAY->value);
        $this->assertEquals(1, DayOfWeek::MONDAY->value);
        $this->assertEquals(2, DayOfWeek::TUESDAY->value);
        $this->assertEquals(3, DayOfWeek::WEDNESDAY->value);
        $this->assertEquals(4, DayOfWeek::THURSDAY->value);
        $this->assertEquals(5, DayOfWeek::FRIDAY->value);
        $this->assertEquals(6, DayOfWeek::SATURDAY->value);
    }

    /** @test */
    public function enum_has_correct_labels(): void
    {
        $this->assertEquals('Sunday', DayOfWeek::SUNDAY->label());
        $this->assertEquals('Monday', DayOfWeek::MONDAY->label());
        $this->assertEquals('Tuesday', DayOfWeek::TUESDAY->label());
        $this->assertEquals('Wednesday', DayOfWeek::WEDNESDAY->label());
        $this->assertEquals('Thursday', DayOfWeek::THURSDAY->label());
        $this->assertEquals('Friday', DayOfWeek::FRIDAY->label());
        $this->assertEquals('Saturday', DayOfWeek::SATURDAY->label());
    }

    /** @test */
    public function enum_has_correct_arabic_labels(): void
    {
        $this->assertEquals('الأحد', DayOfWeek::SUNDAY->labelAr());
        $this->assertEquals('الإثنين', DayOfWeek::MONDAY->labelAr());
        $this->assertEquals('الثلاثاء', DayOfWeek::TUESDAY->labelAr());
        $this->assertEquals('الأربعاء', DayOfWeek::WEDNESDAY->labelAr());
        $this->assertEquals('الخميس', DayOfWeek::THURSDAY->labelAr());
        $this->assertEquals('الجمعة', DayOfWeek::FRIDAY->labelAr());
        $this->assertEquals('السبت', DayOfWeek::SATURDAY->labelAr());
    }

    /** @test */
    public function enum_has_correct_short_labels(): void
    {
        $this->assertEquals('Sun', DayOfWeek::SUNDAY->shortLabel());
        $this->assertEquals('Mon', DayOfWeek::MONDAY->shortLabel());
        $this->assertEquals('Tue', DayOfWeek::TUESDAY->shortLabel());
        $this->assertEquals('Wed', DayOfWeek::WEDNESDAY->shortLabel());
        $this->assertEquals('Thu', DayOfWeek::THURSDAY->shortLabel());
        $this->assertEquals('Fri', DayOfWeek::FRIDAY->shortLabel());
        $this->assertEquals('Sat', DayOfWeek::SATURDAY->shortLabel());
    }

    /** @test */
    public function enum_values_returns_all_values(): void
    {
        $values = DayOfWeek::values();

        $this->assertCount(7, $values);
        $this->assertEquals([0, 1, 2, 3, 4, 5, 6], $values);
    }

    /** @test */
    public function from_date_returns_correct_day(): void
    {
        // 2025-01-19 is a Sunday
        $sunday = Carbon::parse('2025-01-19');
        $this->assertEquals(DayOfWeek::SUNDAY, DayOfWeek::fromDate($sunday));

        // 2025-01-20 is a Monday
        $monday = Carbon::parse('2025-01-20');
        $this->assertEquals(DayOfWeek::MONDAY, DayOfWeek::fromDate($monday));
    }
}
