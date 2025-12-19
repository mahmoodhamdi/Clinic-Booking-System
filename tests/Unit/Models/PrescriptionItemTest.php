<?php

namespace Tests\Unit\Models;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_prescription_item_belongs_to_prescription(): void
    {
        $prescription = Prescription::factory()->create();
        $item = PrescriptionItem::factory()->create([
            'prescription_id' => $prescription->id,
        ]);

        $this->assertInstanceOf(Prescription::class, $item->prescription);
        $this->assertEquals($prescription->id, $item->prescription->id);
    }

    public function test_full_dosage_text_accessor(): void
    {
        $item = PrescriptionItem::factory()->create([
            'medication_name' => 'أموكسيسيلين',
            'dosage' => '500 مجم',
            'frequency' => 'ثلاث مرات يومياً',
            'duration' => '7 أيام',
        ]);

        $expected = 'أموكسيسيلين 500 مجم - ثلاث مرات يومياً لمدة 7 أيام';
        $this->assertEquals($expected, $item->full_dosage_text);
    }

    public function test_full_description_with_instructions_and_quantity(): void
    {
        $item = PrescriptionItem::factory()->create([
            'medication_name' => 'أموكسيسيلين',
            'dosage' => '500 مجم',
            'frequency' => 'ثلاث مرات يومياً',
            'duration' => '7 أيام',
            'instructions' => 'بعد الأكل',
            'quantity' => 21,
        ]);

        $this->assertStringContainsString('(بعد الأكل)', $item->full_description);
        $this->assertStringContainsString('الكمية: 21', $item->full_description);
    }

    public function test_full_description_without_instructions_and_quantity(): void
    {
        $item = PrescriptionItem::factory()->create([
            'medication_name' => 'أموكسيسيلين',
            'dosage' => '500 مجم',
            'frequency' => 'ثلاث مرات يومياً',
            'duration' => '7 أيام',
            'instructions' => null,
            'quantity' => null,
        ]);

        $this->assertStringNotContainsString('(', $item->full_description);
        $this->assertStringNotContainsString('الكمية:', $item->full_description);
    }
}
