<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BallEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'innings_id', 'match_id', 'fixture_id',
        'over_number', 'ball_number', 'ball_sequence', 'legal_ball_sequence',
        'striker_id', 'non_striker_id', 'bowler_id',
        'external_striker_index', 'external_non_striker_index', 'external_bowler_index',
        'batting_team_id', 'bowling_team_id',
        'event_type', 'runs_scored', 'total_runs',
        'is_boundary_four', 'is_boundary_six',
        'extras_type', 'extras_runs',
        'is_legal_delivery', 'is_wicket_ball', 'wicket_id',
        'no_ball_type', 'is_wide_plus_boundary',
        'commentary', 'scorer_notes',
        'is_undo', 'replaced_ball_event_id',
        'offline_uuid', 'is_synced',
    ];

    protected $casts = [
        'over_number' => 'integer',
        'ball_number' => 'integer',
        'ball_sequence' => 'integer',
        'legal_ball_sequence' => 'integer',
        'runs_scored' => 'integer',
        'total_runs' => 'integer',
        'is_boundary_four' => 'boolean',
        'is_boundary_six' => 'boolean',
        'extras_runs' => 'integer',
        'is_legal_delivery' => 'boolean',
        'is_wicket_ball' => 'boolean',
        'is_wide_plus_boundary' => 'boolean',
        'is_undo' => 'boolean',
        'is_synced' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BallEvent $event) {
            if (empty($event->offline_uuid)) {
                $event->offline_uuid = Str::uuid()->toString();
            }
        });
    }

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

    public function striker()
    {
        return $this->belongsTo(User::class, 'striker_id');
    }

    public function nonStriker()
    {
        return $this->belongsTo(User::class, 'non_striker_id');
    }

    public function bowler()
    {
        return $this->belongsTo(User::class, 'bowler_id');
    }

    public function wicket()
    {
        return $this->belongsTo(Wicket::class);
    }

    public function replacedBallEvent()
    {
        return $this->belongsTo(BallEvent::class, 'replaced_ball_event_id');
    }

    public function getDisplayTextAttribute(): string
    {
        if ($this->is_wicket_ball) return 'W';
        if ($this->is_boundary_six) return '6';
        if ($this->is_boundary_four) return '4';
        if ($this->event_type === 'wide') {
            return 'WD' . ($this->total_runs > 1 ? ' ' . $this->total_runs : '');
        }
        if ($this->event_type === 'no_ball') {
            return 'NB' . ($this->runs_scored > 0 ? ' +' . $this->runs_scored : '');
        }
        if ($this->event_type === 'bye') return 'B' . $this->extras_runs;
        if ($this->event_type === 'leg_bye') return 'LB' . $this->extras_runs;
        if ($this->event_type === 'penalty') return 'Pen ' . $this->extras_runs;
        if ($this->total_runs === 0) return '•';
        return (string) $this->total_runs;
    }

    public function getDisplayColorAttribute(): string
    {
        if ($this->is_wicket_ball) return 'red';
        if ($this->is_boundary_six) return 'green';
        if ($this->is_boundary_four) return 'blue';
        if (in_array($this->event_type, ['wide', 'no_ball', 'bye', 'leg_bye'])) return 'yellow';
        if ($this->total_runs === 0) return 'gray';
        return 'dark';
    }

    public function getCommentaryTextAttribute(): string
    {
        if ($this->commentary) return $this->commentary;

        if ($this->is_wicket_ball && $this->wicket) {
            return $this->wicket->short_description;
        }

        if ($this->is_boundary_six) {
            return "{$this->striker?->name} hits a SIX!";
        }
        if ($this->is_boundary_four) {
            return "{$this->striker?->name} hits a FOUR!";
        }
        if ($this->event_type === 'wide') {
            return "Wide ball" . ($this->total_runs > 1 ? ", {$this->total_runs} runs" : '');
        }
        if ($this->event_type === 'no_ball') {
            return "No ball" . ($this->runs_scored > 0 ? ", {$this->runs_scored} run(s) scored" : '');
        }
        if ($this->total_runs === 0) {
            return "No run";
        }
        return "{$this->total_runs} run" . ($this->total_runs > 1 ? 's' : '') . " to {$this->striker?->name}";
    }
}
