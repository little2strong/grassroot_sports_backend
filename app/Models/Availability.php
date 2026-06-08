<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasFactory;

    protected $table = 'availability';

    protected $fillable = [
        'fixture_id', 'user_id', 'team_id', 'status', 'reason', 'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeUnavailable($query)
    {
        return $query->where('status', 'unavailable');
    }

    public function scopeMaybe($query)
    {
        return $query->where('status', 'maybe');
    }

    public function scopeForFixture($query, int $fixtureId)
    {
        return $query->where('fixture_id', $fixtureId);
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => 'Available',
            'maybe' => 'Maybe',
            'unavailable' => 'Unavailable',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'green',
            'maybe' => 'yellow',
            'unavailable' => 'red',
            default => 'gray',
        };
    }
}
