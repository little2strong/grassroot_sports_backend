<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'batting_style', 'bowling_style', 'primary_role',
        'bio', 'total_matches', 'total_runs', 'total_wickets',
        'highest_score', 'total_fifties', 'total_hundreds',
        'total_five_wickets', 'is_public_profile',
    ];

    protected $casts = [
        'total_matches' => 'integer',
        'total_runs' => 'integer',
        'total_wickets' => 'integer',
        'highest_score' => 'integer',
        'total_fifties' => 'integer',
        'total_hundreds' => 'integer',
        'total_five_wickets' => 'integer',
        'is_public_profile' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getBattingStyleLabelAttribute(): string
    {
        return match ($this->batting_style) {
            'right_hand' => 'Right-Handed',
            'left_hand' => 'Left-Handed',
            default => 'Not Set',
        };
    }

    public function getBowlingStyleLabelAttribute(): string
    {
        if (!$this->bowling_style) return 'Not Set';

        $labels = [
            'right_arm_fast' => 'Right Arm Fast',
            'right_arm_fast_medium' => 'Right Arm Fast Medium',
            'right_arm_medium' => 'Right Arm Medium',
            'right_arm_off_break' => 'Right Arm Off Break',
            'right_arm_leg_break' => 'Right Arm Leg Break',
            'left_arm_fast' => 'Left Arm Fast',
            'left_arm_fast_medium' => 'Left Arm Fast Medium',
            'left_arm_medium' => 'Left Arm Medium',
            'left_arm_orthodox' => 'Left Arm Orthodox',
            'left_arm_chinaman' => 'Left Arm Chinaman',
        ];

        return $labels[$this->bowling_style] ?? $this->bowling_style;
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->primary_role) {
            'batsman' => 'Batsman',
            'bowler' => 'Bowler',
            'all_rounder' => 'All-Rounder',
            'wicket_keeper' => 'Wicket-Keeper',
            default => 'Not Set',
        };
    }

    public function getAverageAttribute(): ?float
    {
        if ($this->total_matches === 0) return null;
        $innings = max($this->total_matches, 1);
        return round($this->total_runs / $innings, 2);
    }

    public function recalculateStats(): void
    {
        $this->update([
            'total_matches' => $this->user->battingScores()->distinct('match_id')->count('match_id'),
            'total_runs' => $this->user->battingScores()->sum('runs'),
            'total_wickets' => $this->user->wicketsTaken()->count(),
            'highest_score' => $this->user->battingScores()->max('runs') ?? 0,
            'total_fifties' => $this->user->battingScores()->where('runs', '>=', 50)->where('runs', '<', 100)->count(),
            'total_hundreds' => $this->user->battingScores()->where('runs', '>=', 100)->count(),
            'total_five_wickets' => $this->user->bowlingFigures()->where('wickets', '>=', 5)->count(),
        ]);
    }
}
