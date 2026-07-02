<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BattingScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'innings_id', 'match_id', 'fixture_id', 'user_id', 'team_id',
        'external_player_index', 'external_player_name',
        'batting_order', 'is_on_strike', 'has_batted',
        'runs', 'balls_faced', 'fours', 'sixes',
        'is_out', 'dismissal_type',
        'dismissed_by_bowler_id', 'caught_by_fielder_id',
        'run_out_by_fielder_one_id', 'run_out_by_fielder_two_id',
        'stumped_by_keeper_id', 'wicket_id',
        'dismissal_description',
        'started_at', 'dismissed_at',
    ];

    protected $casts = [
        'batting_order' => 'integer',
        'is_on_strike' => 'boolean',
        'has_batted' => 'boolean',
        'runs' => 'integer',
        'balls_faced' => 'integer',
        'fours' => 'integer',
        'sixes' => 'integer',
        'is_out' => 'boolean',
        'started_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function innings()
    {
        return $this->belongsTo(Innings::class);
    }

    public function match()
    {
        return $this->belongsTo(Matchs::class);
    }

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
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function wicket()
    {
        return $this->belongsTo(Wicket::class);
    }

    public function dismissedByBowler()
    {
        return $this->belongsTo(User::class, 'dismissed_by_bowler_id');
    }

    public function caughtByFielder()
    {
        return $this->belongsTo(User::class, 'caught_by_fielder_id');
    }

    public function getStrikeRateAttribute(): ?float
    {
        if ($this->balls_faced === 0) return null;
        return round(($this->runs / $this->balls_faced) * 100, 2);
    }

    public function getDismissalTextAttribute(): string
    {
        if (!$this->is_out && $this->dismissal_type === 'not_out') return 'not out';
        if (!$this->is_out && in_array($this->dismissal_type, ['retired_hurt', 'absent_hurt'])) {
            return str_replace('_', ' ', $this->dismissal_type);
        }
        if ($this->wicket) return $this->wicket->short_description;
        return $this->dismissal_description ?? 'out';
    }

    public function getScoreDisplayAttribute(): string
    {
        return "{$this->runs} ({$this->balls_faced})";
    }

    public function getBoundarySummaryAttribute(): string
    {
        $parts = [];
        if ($this->fours > 0) $parts[] = "{$this->fours}x4";
        if ($this->sixes > 0) $parts[] = "{$this->sixes}x6";
        return implode(', ', $parts) ?: '-';
    }

    public function getMilestoneAttribute(): ?string
    {
        if ($this->runs >= 100) return '100';
        if ($this->runs >= 50) return '50';
        return null;
    }

    public function isFifty(): bool
    {
        return $this->runs >= 50 && $this->runs < 100;
    }

    public function isHundred(): bool
    {
        return $this->runs >= 100;
    }

    public function isDuck(): bool
    {
        return $this->is_out && $this->runs === 0;
    }
}
