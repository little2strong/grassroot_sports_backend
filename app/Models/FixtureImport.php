<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixtureImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id', 'imported_by', 'source_type',
        'file_path', 'original_filename', 'status',
        'extracted_data', 'parsed_fixtures', 'errors',
        'total_extracted', 'total_imported', 'total_failed',
        'started_processing_at', 'completed_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'parsed_fixtures' => 'array',
        'errors' => 'array',
        'total_extracted' => 'integer',
        'total_imported' => 'integer',
        'total_failed' => 'integer',
        'started_processing_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return strtoupper($this->source_type);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'queued' => 'Queued',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'partial' => 'Partially Completed',
            default => $this->status,
        };
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_extracted === 0) return 0.0;
        return round(($this->total_imported / $this->total_extracted) * 100, 1);
    }

    public function getProcessingTimeAttribute(): ?string
    {
        if (!$this->started_processing_at) return null;
        $end = $this->completed_at ?? now();
        $seconds = $this->started_processing_at->diffInSeconds($end);
        return $seconds . 's';
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
