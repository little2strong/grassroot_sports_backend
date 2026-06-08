<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id', 'team_id', 'user_id', 'amount', 'currency', 'status',
        'due_date', 'paid_by_player_at', 'verified_by', 'verified_at',
        'notes', 'payment_reference', 'assigned_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_by_player_at' => 'datetime',
        'verified_at' => 'datetime',
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

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', 'paid_pending_verification');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'assigned')
                    ->where('due_date', '<', now());
            });
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'assigned' => 'Assigned',
            'paid_pending_verification' => 'Pending Verification',
            'verified' => 'Verified',
            'waived' => 'Waived',
            'overdue' => 'Overdue',
            default => $this->status,
        };
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid_pending_verification',
            'paid_by_player_at' => now(),
        ]);
    }

    public function verify(int $verifierId): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $verifierId,
            'verified_at' => now(),
        ]);
    }

    public function waive(): void
    {
        $this->update(['status' => 'waived']);
    }
}
