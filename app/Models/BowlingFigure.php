<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BowlingFigure extends Model
{
    use HasFactory;

    protected $fillable = [
        'innings_id', 'match_id', 'fixture_id', 'user_id', 'team_id',
        'overs', 'balls_bowled', 'maidens', 'runs_conceded', 'wickets',
        'wides_bowled', 'no_balls_bowled', 'is_current_bowler',
    ];

    protected $casts = [
        'overs' => 'decimal:1',
        'balls_bowled' => 'integer',
        'maidens' => 'integer',
        'runs_conceded' => 'integer',
        'wickets' => 'integer',
        'wides_bowled' => 'integer',
        'no_balls_bowled' => 'integer',
        'is_current_bowler' => 'boolean',
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

    public function bowler()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function getFiguresDisplayAttribute(): string
    {
        return "{$this->overs}-{$this->maidens}-{$this->runs_conceded}-{$this->wickets}";
    }

    public function isFiveWicketHaul(): bool
    {
        return $this->wickets >= 5;
    }

    public function isThreeWicketHaul(): bool
    {
        return $this->wickets >= 3 && $this->wickets < 5;
    }

    public function isMaidenOver(): bool
    {
        return $this->maidens > 0;
    }

    public function getTotalDeliveriesBowledAttribute(): int
    {
        return $this->balls_bowled + $this->wides_bowled + $this->no_balls_bowled;
    }
}
