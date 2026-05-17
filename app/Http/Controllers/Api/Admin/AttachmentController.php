<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Admin\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function index(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $attachments = $medicalRecord->attachments()
            ->with('uploader')
            ->when($request->type === 'images', fn ($q) => $q->images())
            ->when($request->type === 'pdfs', fn ($q) => $q->pdfs())
            ->when($request->type === 'documents', fn ($q) => $q->documents())
            ->latest()
            ->get();

        return ApiResponse::success(AttachmentResource::collection($attachments));
    }

    public function store(StoreAttachmentRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $path = $file->store('attachments/'.$medicalRecord->id, 'public');

        $attachment = $medicalRecord->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => Attachment::getFileType($extension),
            'file_size' => $file->getSize(),
            'description' => $request->description,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->load('uploader');

        return ApiResponse::created(new AttachmentResource($attachment), __('messages.attachments.uploaded'));
    }

    public function show(MedicalRecord $medicalRecord, Attachment $attachment): JsonResponse
    {
        // Ensure attachment belongs to the medical record
        if ($attachment->attachable_id !== $medicalRecord->id || $attachment->attachable_type !== MedicalRecord::class) {
            abort(404, __('messages.attachments.not_found'));
        }

        $attachment->load('uploader');

        return ApiResponse::success(new AttachmentResource($attachment));
    }

    public function destroy(MedicalRecord $medicalRecord, Attachment $attachment): JsonResponse
    {
        // Ensure attachment belongs to the medical record
        if ($attachment->attachable_id !== $medicalRecord->id || $attachment->attachable_type !== MedicalRecord::class) {
            abort(404, __('messages.attachments.not_found'));
        }

        // Delete the file from storage
        $attachment->deleteFile();

        $attachment->delete();

        return ApiResponse::success(null, __('messages.attachments.deleted'));
    }

    public function download(MedicalRecord $medicalRecord, Attachment $attachment)
    {
        // Ensure attachment belongs to the medical record
        if ($attachment->attachable_id !== $medicalRecord->id || $attachment->attachable_type !== MedicalRecord::class) {
            abort(404, __('messages.attachments.not_found'));
        }

        if (! Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, __('messages.attachments.missing_in_storage'));
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }
}
