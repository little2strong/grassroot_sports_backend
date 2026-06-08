<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ball_event_id', 'innings_id', 'match_id', 'fixture_id',
        'dismissed_batter_id', 'dismissal_type',
        'bowler_id', 'fielder_one_id', 'fielder_two_id',
        'runs_at_dismissal', 'description',
    ];

    protected $casts = [
        'runs_at_dismissal' => 'integer',
    ];

    public function ballEvent()
    {
        return $this->belongsTo(BallEvent::class);
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

    public function dismissedBatter()
    {
        return $this->belongsTo(User::class, 'dismissed_batter_id');
    }

    public function bowler()
    {
        return $this->belongsTo(User::class, 'bowler_id');
    }

    public function fielderOne()
    {
        return $this->belongsTo(User::class, 'fielder_one_id');
    }

    public function fielderTwo()
    {
        return $this->belongsTo(User::class, 'fielder_two_id');
    }

    public function getShortDescriptionAttribute(): string
    {
        $bowlerName = $this->bowler?->name ?? '';
        $fielderName = $this->fielderOne?->name ?? '';

        return match ($this->dismissal_type) {
            'bowled' => "b {$bowlerName}",
            'caught' => "c {$fielderName} b {$bowlerName}",
            'lbw' => "lbw b {$bowlerName}",
            'run_out' => "run out" . ($fielderName ? " ({$fielderName})" : ''),
            'stumped' => "st {$fielderName} b {$bowlerName}",
            'hit_wicket' => "hit wicket b {$bowlerName}",
            'caught_and_bowled' => "c & b {$bowlerName}",
            'retired' => "retired",
            'retired_hurt' => "retired hurt",
            'timed_out' => "timed out",
            'obstructing_field' => "obstructing the field",
            'hit_ball_twice' => "hit ball twice",
            default => $this->dismissal_type,
        };
    }

    public function getFullDescriptionAttribute(): string
    {
        $batterName = $this->dismissedBatter?->name ?? 'Unknown';
        $score = $this->getBatterScoreAtDismissal();
        return "{$batterName} {$score} — {$this->short_description}";
    }

    private function getBatterScoreAtDismissal(): string
    {
        $battingScore = BattingScore::where('innings_id', $this->innings_id)
            ->where('user_id', $this->dismissed_batter_id)
            ->first();

        if (!$battingScore) return '';

        $runs = $battingScore->runs;
        $balls = $battingScore->balls_faced;
        $fours = $battingScore->fours;
        $sixes = $battingScore->sixes;

        $parts = ["{$runs} ({$balls})"];
        if ($fours > 0) $parts[] = "{$fours}x4";
        if ($sixes > 0) $parts[] = "{$sixes}x6";

        return implode(', ', $parts);
    }

    public function getDismissalTypeLabelAttribute(): string
    {
        return match ($this->dismissal_type) {
            'bowled' => 'Bowled',
            'caught' => 'Caught',
            'lbw' => 'LBW',
            'run_out' => 'Run Out',
            'stumped' => 'Stumped',
            'hit_wicket' => 'Hit Wicket',
            'caught_and_bowled' => 'Caught & Bowled',
            'retired' => 'Retired',
            'retired_hurt' => 'Retired Hurt',
            'timed_out' => 'Timed Out',
            'obstructing_field' => 'Obstructing Field',
            'hit_ball_twice' => 'Hit Ball Twice',
            default => $this->dismissal_type,
        };
    }

    public function involvesBowler(): bool
    {
        return in_array($this->dismissal_type, [
            'bowled', 'caught', 'lbw', 'stumped',
            'hit_wicket', 'caught_and_bowled',
        ]);
    }
}
