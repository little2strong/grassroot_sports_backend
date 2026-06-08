<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'innings_id', 'match_id', 'over_number', 'bowler_id',
        'bowling_team_id', 'runs', 'wickets', 'extras', 'is_maiden', 'balls',
    ];

    protected $casts = [
        'over_number' => 'integer',
        'runs' => 'integer',
        'wickets' => 'integer',
        'extras' => 'integer',
        'is_maiden' => 'boolean',
        'balls' => 'array',
    ];

    public function innings()
    {
        return $this->belongsTo(Innings::class);
    }

    public function match()
    {
        return $this->belongsTo(Matchs::class);
    }

    public function bowler()
    {
        return $this->belongsTo(User::class, 'bowler_id');
    }

    public function bowlingTeam()
    {
        return $this->belongsTo(Team::class, 'bowling_team_id');
    }

    public function getOverLabelAttribute(): string
    {
        return 'Over ' . ($this->over_number + 1);
    }

    public function getBallCountAttribute(): int
    {
        return is_array($this->balls) ? count($this->balls) : 0;
    }

    public function isWicketMaiden(): bool
    {
        return $this->is_maiden && $this->wickets > 0;
    }

    public function getDisplayTextAttribute(): string
    {
        $text = "{$this->runs} run" . ($this->runs !== 1 ? 's' : '');
        if ($this->wickets > 0) {
            $text .= ", {$this->wickets} wicket" . ($this->wickets > 1 ? 's' : '');
        }
        if ($this->is_maiden) {
            $text = 'Maiden' . ($this->wickets > 0 ? '!' : '');
        }
        return $text;
    }
}
