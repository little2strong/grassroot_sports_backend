<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerShortageRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id', 'team_id', 'user_id', 'requested_by',
        'request_type', 'reason',
        'target_fixture_id', 'target_team_id',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function targetFixture()
    {
        return $this->belongsTo(Fixture::class, 'target_fixture_id');
    }

    public function targetTeam()
    {
        return $this->belongsTo(Team::class, 'target_team_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForFixture($query, int $fixtureId)
    {
        return $query->where('fixture_id', $fixtureId);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function reject(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function markFulfilled(): void
    {
        $this->update(['status' => 'fulfilled']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return match ($this->request_type) {
            'unavailability' => 'Unavailability',
            'volunteer' => 'Volunteer',
            'external_request' => 'External Request',
            default => $this->request_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
            default => $this->status,
        };
    }
}
