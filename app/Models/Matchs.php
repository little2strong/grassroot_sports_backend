<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matchs extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'fixture_id',
        'current_innings_number', 'current_over_number', 'current_ball_number',
        'total_legal_deliveries', 'is_paused', 'paused_at', 'resumed_at',
        'first_innings_id', 'second_innings_id',
        'powerplay_config', 'current_powerplay',
    ];

    protected $casts = [
        'current_innings_number' => 'integer',
        'current_over_number' => 'integer',
        'current_ball_number' => 'integer',
        'total_legal_deliveries' => 'integer',
        'is_paused' => 'boolean',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'powerplay_config' => 'array',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function innings()
    {
        return $this->hasMany(Innings::class);
    }

    public function firstInnings()
    {
        return $this->belongsTo(Innings::class, 'first_innings_id');
    }

    public function secondInnings()
    {
        return $this->belongsTo(Innings::class, 'second_innings_id');
    }

    public function currentInnings(): ?Innings
    {
        return $this->current_innings_number === 1
            ? $this->firstInnings
            : $this->secondInnings;
    }

    public function ballEvents()
    {
        return $this->hasManyThrough(BallEvent::class, Innings::class);
    }

    public function scorers()
    {
        return $this->hasMany(MatchScorer::class);
    }

    public function summary()
    {
        return $this->hasOne(MatchSummary::class);
    }

    public function getCurrentOverDisplayAttribute(): string
    {
        return $this->current_over_number . '.' . $this->current_ball_number;
    }

    public function pause(): void
    {
        $this->update([
            'is_paused' => true,
            'paused_at' => now(),
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'is_paused' => false,
            'resumed_at' => now(),
        ]);
    }

    public function getElapsedTimeAttribute(): string
    {
        $start = $this->firstInnings?->started_at;
        $end = $this->fixture->completed_at ?? now();

        if (!$start) return '0m';

        $minutes = $start->diffInMinutes($end);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    }
}
