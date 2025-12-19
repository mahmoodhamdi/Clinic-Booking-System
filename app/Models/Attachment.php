<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ==================== Relationships ====================

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ==================== Accessors ====================

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFullUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->file_type, ['image', 'jpg', 'jpeg', 'png', 'gif']);
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'pdf';
    }

    public function getIsDocumentAttribute(): bool
    {
        return in_array($this->file_type, ['doc', 'docx', 'document']);
    }

    public function getIconAttribute(): string
    {
        if ($this->is_image) {
            return 'image';
        } elseif ($this->is_pdf) {
            return 'pdf';
        } elseif ($this->is_document) {
            return 'document';
        }

        return 'file';
    }

    // ==================== Scopes ====================

    public function scopeImages($query)
    {
        return $query->whereIn('file_type', ['image', 'jpg', 'jpeg', 'png', 'gif']);
    }

    public function scopePdfs($query)
    {
        return $query->where('file_type', 'pdf');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('file_type', ['doc', 'docx', 'document']);
    }

    public function scopeUploadedBy($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    // ==================== Methods ====================

    public function deleteFile(): bool
    {
        if (Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }

        return false;
    }

    public static function getFileType(string $extension): string
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $documentExtensions = ['doc', 'docx'];

        $extension = strtolower($extension);

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif ($extension === 'pdf') {
            return 'pdf';
        } elseif (in_array($extension, $documentExtensions)) {
            return 'document';
        }

        return 'file';
    }
}
