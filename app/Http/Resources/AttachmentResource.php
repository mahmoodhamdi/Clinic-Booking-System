<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attachable_type' => class_basename($this->attachable_type),
            'attachable_id' => $this->attachable_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'size_formatted' => $this->size_formatted,
            'description' => $this->description,
            'url' => $this->url,
            'full_url' => $this->full_url,
            'is_image' => $this->is_image,
            'is_pdf' => $this->is_pdf,
            'is_document' => $this->is_document,
            'icon' => $this->icon,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
