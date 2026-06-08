<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Squad extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id', 'team_id', 'user_id', 'position',
        'jersey_number', 'is_captain', 'is_wicket_keeper', 'added_by',
    ];

    protected $casts = [
        'jersey_number' => 'integer',
        'is_captain' => 'boolean',
        'is_wicket_keeper' => 'boolean',
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

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopePlayingXi($query)
    {
        return $query->where('position', 'playing_xi');
    }

    public function scopeReserves($query)
    {
        return $query->where('position', 'reserve');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeForFixture($query, int $fixtureId)
    {
        return $query->where('fixture_id', $fixtureId);
    }

    public function getPositionLabelAttribute(): string
    {
        return match ($this->position) {
            'playing_xi' => 'Playing XI',
            'reserve' => 'Reserve',
            'twelfth_man' => '12th Man',
            default => $this->position,
        };
    }
}
