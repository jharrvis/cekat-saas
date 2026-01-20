<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_base_id',
        'name',
        'type',
        'file_path',
        'url',
        'content',
        'chunks',
        'status',
    ];

    protected $casts = [
        'chunks' => 'array',
    ];

    /**
     * Get the knowledge base that owns the document.
     */
    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    /**
     * Get the chunks for the document.
     */
    public function documentChunks()
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('chunk_index');
    }

    /**
     * Check if document is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if document processing failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get chunk count.
     */
    public function getChunkCountAttribute(): int
    {
        if ($this->chunks) {
            return count($this->chunks);
        }
        return 0;
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_path) {
            return 'N/A';
        }

        $path = storage_path('app/public/' . $this->file_path);
        if (!file_exists($path)) {
            return 'N/A';
        }

        $bytes = filesize($path);
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get icon class based on document type.
     */
    public function getIconClassAttribute(): string
    {
        return match ($this->type) {
            'pdf' => 'fa-file-pdf text-red-500',
            'docx' => 'fa-file-word text-blue-500',
            'txt' => 'fa-file-alt text-gray-500',
            'url' => 'fa-globe text-green-500',
            default => 'fa-file text-gray-500',
        };
    }
}
