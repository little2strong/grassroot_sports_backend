<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Fixture extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id', 'home_team_id', 'home_opponent_name', 'home_opponent_players', 'away_team_id', 'away_opponent_name', 'away_opponent_players', 'venue_id',
        'scheduled_date', 'scheduled_time', 'match_type',
        'overs_per_innings', 'ball_type', 'status',
        'toss_winner_team_id', 'toss_decision',
        'result_type', 'result_margin', 'winner_team_id',
        'man_of_the_match_id', 'result_description',
        'home_team_runs', 'home_team_wickets', 'home_team_overs',
        'away_team_runs', 'away_team_wickets', 'away_team_overs',
        'is_public', 'public_share_slug', 'created_by', 'scorer_user_id', 'scorer_assigned_at', 'club_plays_home',
        'published_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'home_opponent_players' => 'array',
        'away_opponent_players' => 'array',
        'overs_per_innings' => 'integer',
        'home_team_runs' => 'integer',
        'home_team_wickets' => 'integer',
        'home_team_overs' => 'decimal:1',
        'away_team_runs' => 'integer',
        'away_team_wickets' => 'integer',
        'away_team_overs' => 'decimal:1',
        'is_public' => 'boolean',
        'club_plays_home' => 'boolean',
        'scorer_assigned_at' => 'datetime',
        'published_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Fixture $fixture) {
            if (empty($fixture->public_share_slug)) {
                $fixture->public_share_slug = Str::uuid()->toString();
            }
        });
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function tossWinner()
    {
        return $this->belongsTo(Team::class, 'toss_winner_team_id');
    }

    public function winner()
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function manOfTheMatch()
    {
        return $this->belongsTo(User::class, 'man_of_the_match_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scorer()
    {
        return $this->belongsTo(User::class, 'scorer_user_id');
    }

    public function availability()
    {
        return $this->hasMany(Availability::class);
    }

    public function squads()
    {
        return $this->hasMany(Squad::class);
    }

    public function matchFees()
    {
        return $this->hasMany(MatchFee::class);
    }

    public function match()
    {
        return $this->hasOne(Matchs::class);
    }

    public function summary()
    {
        return $this->hasOne(MatchSummary::class);
    }

    public function followers()
    {
        return $this->hasMany(Follower::class);
    }

    public function shortageRequests()
    {
        return $this->hasMany(PlayerShortageRequest::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', '!=', 'draft');
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['draft', 'published'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', '!=', 'draft');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('home_team_id', $teamId)
            ->orWhere('away_team_id', $teamId);
    }

    public function scopeForClub($query, int $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    public function getHomeDisplayNameAttribute(): ?string
    {
        return $this->homeTeam?->name ?? $this->home_opponent_name;
    }

    public function getAwayDisplayNameAttribute(): ?string
    {
        return $this->awayTeam?->name ?? $this->away_opponent_name;
    }

    public function isHomeExternal(): bool
    {
        return is_null($this->home_team_id) && filled($this->home_opponent_name);
    }

    public function isAwayExternal(): bool
    {
        return is_null($this->away_team_id) && filled($this->away_opponent_name);
    }

    public function clubPlaysHome(): bool
    {
        return $this->club_plays_home ?? true;
    }

    public function clubTeam()
    {
        return $this->clubPlaysHome() ? $this->homeTeam : $this->awayTeam;
    }

    public function clubTeamId(): ?int
    {
        return $this->clubPlaysHome() ? $this->home_team_id : $this->away_team_id;
    }

    public function opponentPlayers(): ?array
    {
        return $this->clubPlaysHome() ? $this->away_opponent_players : $this->home_opponent_players;
    }

    public function opponentName(): ?string
    {
        return $this->clubPlaysHome() ? $this->away_opponent_name : $this->home_opponent_name;
    }

    public function hasOpponentPlayers(): bool
    {
        return filled($this->opponentPlayers());
    }

    public function hasClubMatchSquad(): bool
    {
        $teamId = $this->clubTeamId();

        if (!$teamId) {
            return false;
        }

        return $this->squads()
            ->where('team_id', $teamId)
            ->where('position', 'playing_xi')
            ->exists();
    }

    public function isMatchReady(): bool
    {
        return $this->clubTeamId() !== null
            && $this->hasClubMatchSquad()
            && $this->hasOpponentPlayers()
            && $this->scorer_user_id !== null;
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/match/{$this->public_share_slug}");
    }

    public function getResultTextAttribute(): ?string
    {
        if (!$this->result_type || !$this->winner_team_id) {
            return null;
        }

        $winner = $this->winner?->short_name ?? $this->winner?->name;

        return match ($this->result_type) {
            'runs' => "{$winner} won by {$this->result_margin} runs",
            'wickets' => "{$winner} won by {$this->result_margin} wickets",
            'tie' => "Match tied",
            'dl_method' => "{$winner} won by DLS method",
            'draw' => "Match drawn",
            'no_result' => "No result",
            'cancelled' => "Match cancelled",
            default => $this->result_description,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'live' => 'Live',
            'paused' => 'Paused',
            'completed' => 'Completed',
            'abandoned' => 'Abandoned',
            'cancelled' => 'Cancelled',
            'postponed' => 'Postponed',
            default => $this->status,
        };
    }

    public function getMatchTypeLabelAttribute(): string
    {
        return match ($this->match_type) {
            't10' => 'T10',
            't20' => 'T20',
            'odi_50' => 'ODI (50)',
            'odi_40' => 'ODI (40)',
            'test' => 'Test',
            'custom' => 'Custom',
            default => $this->match_type,
        };
    }

    public function getHomeScoreDisplayAttribute(): string
    {
        return "{$this->home_team_runs}/{$this->home_team_wickets} ({$this->home_team_overs})";
    }

    public function getAwayScoreDisplayAttribute(): string
    {
        return "{$this->away_team_runs}/{$this->away_team_wickets} ({$this->away_team_overs})";
    }

    public function isLive(): bool
    {
        return in_array($this->status, ['live', 'paused']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasResult(): bool
    {
        return !is_null($this->result_type);
    }
}
