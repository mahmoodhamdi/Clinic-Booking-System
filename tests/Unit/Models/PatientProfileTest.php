<?php

namespace Tests\Unit\Models;

use App\Enums\BloodType;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function belongs_to_user(): void
    {
        $patient = User::factory()->patient()->create();
        $profile = PatientProfile::factory()->forUser($patient)->create();

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($patient->id, $profile->user->id);
    }

    /** @test */
    public function casts_blood_type_to_enum(): void
    {
        $profile = PatientProfile::factory()->withBloodType(BloodType::A_POSITIVE)->create();

        $this->assertInstanceOf(BloodType::class, $profile->blood_type);
        $this->assertEquals(BloodType::A_POSITIVE, $profile->blood_type);
    }

    /** @test */
    public function casts_allergies_to_array(): void
    {
        $allergies = ['البنسلين', 'الأسبرين'];
        $profile = PatientProfile::factory()->withAllergies($allergies)->create();

        $this->assertIsArray($profile->allergies);
        $this->assertEquals($allergies, $profile->allergies);
    }

    /** @test */
    public function casts_chronic_diseases_to_array(): void
    {
        $diseases = ['السكري', 'ضغط الدم'];
        $profile = PatientProfile::factory()->withChronicDiseases($diseases)->create();

        $this->assertIsArray($profile->chronic_diseases);
        $this->assertEquals($diseases, $profile->chronic_diseases);
    }

    /** @test */
    public function casts_current_medications_to_array(): void
    {
        $medications = ['ميتفورمين 500mg', 'أملوديبين 5mg'];
        $profile = PatientProfile::factory()->withMedications($medications)->create();

        $this->assertIsArray($profile->current_medications);
        $this->assertEquals($medications, $profile->current_medications);
    }

    /** @test */
    public function has_blood_type_label_accessor(): void
    {
        $profile = PatientProfile::factory()->withBloodType(BloodType::A_POSITIVE)->create();

        $this->assertEquals('A موجب', $profile->blood_type_label);
    }

    /** @test */
    public function has_allergies_list_accessor(): void
    {
        $allergies = ['البنسلين', 'الأسبرين'];
        $profile = PatientProfile::factory()->withAllergies($allergies)->create();

        $this->assertEquals('البنسلين, الأسبرين', $profile->allergies_list);
    }

    /** @test */
    public function allergies_list_returns_empty_string_when_no_allergies(): void
    {
        $profile = PatientProfile::factory()->create(['allergies' => null]);

        $this->assertEquals('', $profile->allergies_list);
    }

    /** @test */
    public function has_emergency_contact_returns_true_when_contact_exists(): void
    {
        $profile = PatientProfile::factory()->withEmergencyContact()->create();

        $this->assertTrue($profile->has_emergency_contact);
    }

    /** @test */
    public function has_emergency_contact_returns_false_when_no_contact(): void
    {
        $profile = PatientProfile::factory()->withoutEmergencyContact()->create();

        $this->assertFalse($profile->has_emergency_contact);
    }

    /** @test */
    public function has_insurance_returns_true_when_insurance_exists(): void
    {
        $profile = PatientProfile::factory()->withInsurance()->create();

        $this->assertTrue($profile->has_insurance);
    }

    /** @test */
    public function has_insurance_returns_false_when_no_insurance(): void
    {
        $profile = PatientProfile::factory()->withoutInsurance()->create();

        $this->assertFalse($profile->has_insurance);
    }

    /** @test */
    public function is_complete_returns_true_when_profile_is_complete(): void
    {
        $profile = PatientProfile::factory()->complete()->create();

        $this->assertTrue($profile->is_complete);
    }

    /** @test */
    public function is_complete_returns_false_when_profile_is_incomplete(): void
    {
        $profile = PatientProfile::factory()->incomplete()->create();

        $this->assertFalse($profile->is_complete);
    }

    /** @test */
    public function can_check_if_has_allergy(): void
    {
        $profile = PatientProfile::factory()->withAllergies(['البنسلين', 'الأسبرين'])->create();

        $this->assertTrue($profile->hasAllergy('البنسلين'));
        $this->assertFalse($profile->hasAllergy('السلفا'));
    }

    /** @test */
    public function can_check_if_has_chronic_disease(): void
    {
        $profile = PatientProfile::factory()->withChronicDiseases(['السكري'])->create();

        $this->assertTrue($profile->hasChronicDisease('السكري'));
        $this->assertFalse($profile->hasChronicDisease('الربو'));
    }

    /** @test */
    public function can_add_allergy(): void
    {
        $profile = PatientProfile::factory()->withAllergies(['البنسلين'])->create();

        $profile->addAllergy('الأسبرين');

        $this->assertTrue($profile->fresh()->hasAllergy('الأسبرين'));
    }

    /** @test */
    public function can_remove_allergy(): void
    {
        $profile = PatientProfile::factory()->withAllergies(['البنسلين', 'الأسبرين'])->create();

        $profile->removeAllergy('الأسبرين');

        $this->assertFalse($profile->fresh()->hasAllergy('الأسبرين'));
        $this->assertTrue($profile->fresh()->hasAllergy('البنسلين'));
    }

    /** @test */
    public function can_add_medication(): void
    {
        $profile = PatientProfile::factory()->withMedications(['ميتفورمين'])->create();

        $profile->addMedication('أملوديبين');

        $this->assertContains('أملوديبين', $profile->fresh()->current_medications);
    }

    /** @test */
    public function can_remove_medication(): void
    {
        $profile = PatientProfile::factory()->withMedications(['ميتفورمين', 'أملوديبين'])->create();

        $profile->removeMedication('أملوديبين');

        $this->assertNotContains('أملوديبين', $profile->fresh()->current_medications);
    }
}
