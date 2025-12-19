<?php

namespace Tests\Unit\Models;

use App\Models\Attachment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_belongs_to_uploader(): void
    {
        $uploader = User::factory()->create();
        $attachment = Attachment::factory()->create([
            'uploaded_by' => $uploader->id,
        ]);

        $this->assertInstanceOf(User::class, $attachment->uploader);
        $this->assertEquals($uploader->id, $attachment->uploader->id);
    }

    public function test_morph_to_attachable(): void
    {
        $medicalRecord = MedicalRecord::factory()->create();
        $attachment = Attachment::factory()->forMedicalRecord($medicalRecord)->create();

        $this->assertInstanceOf(MedicalRecord::class, $attachment->attachable);
        $this->assertEquals($medicalRecord->id, $attachment->attachable->id);
    }

    public function test_is_image_attribute(): void
    {
        $imageAttachment = Attachment::factory()->image()->create();
        $pdfAttachment = Attachment::factory()->pdf()->create();

        $this->assertTrue($imageAttachment->is_image);
        $this->assertFalse($pdfAttachment->is_image);
    }

    public function test_is_pdf_attribute(): void
    {
        $pdfAttachment = Attachment::factory()->pdf()->create();
        $imageAttachment = Attachment::factory()->image()->create();

        $this->assertTrue($pdfAttachment->is_pdf);
        $this->assertFalse($imageAttachment->is_pdf);
    }

    public function test_is_document_attribute(): void
    {
        $docAttachment = Attachment::factory()->document()->create();
        $pdfAttachment = Attachment::factory()->pdf()->create();

        $this->assertTrue($docAttachment->is_document);
        $this->assertFalse($pdfAttachment->is_document);
    }

    public function test_icon_attribute(): void
    {
        $imageAttachment = Attachment::factory()->image()->create();
        $pdfAttachment = Attachment::factory()->pdf()->create();
        $docAttachment = Attachment::factory()->document()->create();

        $this->assertEquals('image', $imageAttachment->icon);
        $this->assertEquals('pdf', $pdfAttachment->icon);
        $this->assertEquals('document', $docAttachment->icon);
    }

    public function test_size_formatted_bytes(): void
    {
        $attachment = Attachment::factory()->create([
            'file_size' => 500,
        ]);

        $this->assertEquals('500 bytes', $attachment->size_formatted);
    }

    public function test_size_formatted_kb(): void
    {
        $attachment = Attachment::factory()->create([
            'file_size' => 2048,
        ]);

        $this->assertEquals('2 KB', $attachment->size_formatted);
    }

    public function test_size_formatted_mb(): void
    {
        $attachment = Attachment::factory()->create([
            'file_size' => 2097152,
        ]);

        $this->assertEquals('2 MB', $attachment->size_formatted);
    }

    public function test_get_file_type_for_images(): void
    {
        $this->assertEquals('image', Attachment::getFileType('jpg'));
        $this->assertEquals('image', Attachment::getFileType('jpeg'));
        $this->assertEquals('image', Attachment::getFileType('png'));
        $this->assertEquals('image', Attachment::getFileType('gif'));
        $this->assertEquals('image', Attachment::getFileType('webp'));
        $this->assertEquals('image', Attachment::getFileType('PNG'));
    }

    public function test_get_file_type_for_pdf(): void
    {
        $this->assertEquals('pdf', Attachment::getFileType('pdf'));
        $this->assertEquals('pdf', Attachment::getFileType('PDF'));
    }

    public function test_get_file_type_for_documents(): void
    {
        $this->assertEquals('document', Attachment::getFileType('doc'));
        $this->assertEquals('document', Attachment::getFileType('docx'));
    }

    public function test_get_file_type_for_unknown(): void
    {
        $this->assertEquals('file', Attachment::getFileType('txt'));
        $this->assertEquals('file', Attachment::getFileType('xyz'));
    }

    public function test_scope_images(): void
    {
        Attachment::factory()->count(2)->image()->create();
        Attachment::factory()->pdf()->create();

        $this->assertCount(2, Attachment::images()->get());
    }

    public function test_scope_pdfs(): void
    {
        Attachment::factory()->count(2)->pdf()->create();
        Attachment::factory()->image()->create();

        $this->assertCount(2, Attachment::pdfs()->get());
    }

    public function test_scope_documents(): void
    {
        Attachment::factory()->count(2)->document()->create();
        Attachment::factory()->image()->create();

        $this->assertCount(2, Attachment::documents()->get());
    }

    public function test_scope_uploaded_by(): void
    {
        $user = User::factory()->create();
        Attachment::factory()->count(2)->uploadedBy($user)->create();
        Attachment::factory()->create();

        $this->assertCount(2, Attachment::uploadedBy($user->id)->get());
    }
}
