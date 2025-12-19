<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ==================== Admin Tests ====================

    public function test_admin_can_list_attachments_for_medical_record(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        Attachment::factory()->count(3)->forMedicalRecord($medicalRecord)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/medical-records/{$medicalRecord->id}/attachments");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_attachments_by_type(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        Attachment::factory()->count(2)->image()->forMedicalRecord($medicalRecord)->create();
        Attachment::factory()->pdf()->forMedicalRecord($medicalRecord)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/medical-records/{$medicalRecord->id}/attachments?type=images");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_upload_attachment(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $file = UploadedFile::fake()->image('xray.jpg', 800, 600);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/medical-records/{$medicalRecord->id}/attachments", [
                'file' => $file,
                'description' => 'صورة أشعة',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.description', 'صورة أشعة')
            ->assertJsonPath('data.file_type', 'image');

        $this->assertDatabaseHas('attachments', [
            'attachable_id' => $medicalRecord->id,
            'description' => 'صورة أشعة',
        ]);
    }

    public function test_admin_can_upload_pdf_attachment(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/medical-records/{$medicalRecord->id}/attachments", [
                'file' => $file,
                'description' => 'تقرير طبي',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.file_type', 'pdf');
    }

    public function test_admin_can_view_attachment(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();
        $attachment = Attachment::factory()->forMedicalRecord($medicalRecord)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/medical-records/{$medicalRecord->id}/attachments/{$attachment->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $attachment->id);
    }

    public function test_admin_cannot_view_attachment_from_different_medical_record(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();
        $otherMedicalRecord = MedicalRecord::factory()->create();
        $attachment = Attachment::factory()->forMedicalRecord($otherMedicalRecord)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/medical-records/{$medicalRecord->id}/attachments/{$attachment->id}");

        $response->assertNotFound();
    }

    public function test_admin_can_delete_attachment(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();
        $attachment = Attachment::factory()->forMedicalRecord($medicalRecord)->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/medical-records/{$medicalRecord->id}/attachments/{$attachment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id,
        ]);
    }

    // ==================== Validation Tests ====================

    public function test_file_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/medical-records/{$medicalRecord->id}/attachments", [
                'description' => 'وصف',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_must_be_valid_type(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $file = UploadedFile::fake()->create('file.exe', 1024);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/medical-records/{$medicalRecord->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_size_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        // 15MB file (limit is 10MB)
        $file = UploadedFile::fake()->create('large.jpg', 15360, 'image/jpeg');

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/medical-records/{$medicalRecord->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    // ==================== Access Control Tests ====================

    public function test_patient_cannot_upload_attachments(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($patient)
            ->postJson("/api/admin/medical-records/{$medicalRecord->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_secretary_can_upload_attachments(): void
    {
        $secretary = User::factory()->secretary()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($secretary)
            ->postJson("/api/admin/medical-records/{$medicalRecord->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertCreated();
    }
}
