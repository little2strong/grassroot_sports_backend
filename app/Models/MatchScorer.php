<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchScorer extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id', 'fixture_id', 'user_id', 'role', 'assigned_by', 'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(Matchs::class);
    }

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function scorer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isPrimary(): bool
    {
        return $this->role === 'primary_scorer';
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role === 'primary_scorer' ? 'Primary Scorer' : 'Assistant Scorer';
    }
}
