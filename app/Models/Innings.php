<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Innings extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id', 'fixture_id', 'batting_team_id', 'bowling_team_id',
        'innings_number', 'runs', 'wickets', 'overs', 'legal_deliveries',
        'extras_total', 'wides', 'no_balls', 'byes', 'leg_byes', 'penalty_runs',
        'target', 'striker_id', 'non_striker_id', 'current_bowler_id',
        'result', 'result_note', 'total_batters',
        'started_at', 'ended_at',
    ];

    protected $casts = [
        'innings_number' => 'integer',
        'runs' => 'integer',
        'wickets' => 'integer',
        'overs' => 'decimal:1',
        'legal_deliveries' => 'integer',
        'extras_total' => 'integer',
        'wides' => 'integer',
        'no_balls' => 'integer',
        'byes' => 'integer',
        'leg_byes' => 'integer',
        'penalty_runs' => 'integer',
        'target' => 'integer',
        'total_batters' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(Matchs::class);
    }

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function battingTeam()
    {
        return $this->belongsTo(Team::class, 'batting_team_id');
    }

    public function bowlingTeam()
    {
        return $this->belongsTo(Team::class, 'bowling_team_id');
    }

    public function striker()
    {
        return $this->belongsTo(User::class, 'striker_id');
    }

    public function nonStriker()
    {
        return $this->belongsTo(User::class, 'non_striker_id');
    }

    public function currentBowler()
    {
        return $this->belongsTo(User::class, 'current_bowler_id');
    }

    public function ballEvents()
    {
        return $this->hasMany(BallEvent::class)->orderBy('ball_sequence');
    }

    public function battingScores()
    {
        return $this->hasMany(BattingScore::class)->orderBy('batting_order');
    }

    public function bowlingFigures()
    {
        return $this->hasMany(BowlingFigure::class);
    }

    public function wickets()
    {
        return $this->hasMany(Wicket::class);
    }

    public function overSummaries()
    {
        return $this->hasMany(OverSummary::class)->orderBy('over_number');
    }

    public function getRunRateAttribute(): float
    {
        if ($this->legal_deliveries === 0) return 0.0;
        return round(($this->runs / $this->legal_deliveries) * 6, 2);
    }

    public function getRequiredRunRateAttribute(): ?float
    {
        if (!$this->target || $this->legal_deliveries === 0) return null;
        $needed = $this->target - $this->runs;
        $maxDeliveries = $this->match->fixture->overs_per_innings * 6;
        $remaining = $maxDeliveries - $this->legal_deliveries;
        if ($remaining <= 0) return null;
        return round(($needed / $remaining) * 6, 2);
    }

    public function getRemainingBallsAttribute(): int
    {
        return ($this->match->fixture->overs_per_innings * 6) - $this->legal_deliveries;
    }

    public function isAllOut(): bool
    {
        return $this->wickets >= ($this->total_batters - 1);
    }

    public function isOversComplete(): bool
    {
        return $this->legal_deliveries >= ($this->match->fixture->overs_per_innings * 6);
    }

    public function isTargetAchieved(): bool
    {
        return $this->target !== null && $this->runs >= $this->target;
    }

    public function isInProgress(): bool
    {
        return $this->result === 'in_progress';
    }

    public function getScoreDisplayAttribute(): string
    {
        return "{$this->runs}/{$this->wickets} ({$this->overs})";
    }

    public function getExtrasDisplayAttribute(): string
    {
        $parts = [];
        if ($this->wides > 0) $parts[] = "Wd {$this->wides}";
        if ($this->no_balls > 0) $parts[] = "Nb {$this->no_balls}";
        if ($this->byes > 0) $parts[] = "B {$this->byes}";
        if ($this->leg_byes > 0) $parts[] = "Lb {$this->leg_byes}";
        if ($this->penalty_runs > 0) $parts[] = "Pen {$this->penalty_runs}";
        return implode(', ', $parts) ?: '0';
    }
}
