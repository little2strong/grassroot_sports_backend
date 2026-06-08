<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'date_of_birth',
        'gender',
        'is_active',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function playerProfile()
    {
        return $this->hasOne(PlayerProfile::class);
    }

    public function clubMemberships()
    {
        return $this->hasMany(ClubMember::class);
    }

    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_members')
            ->withPivot('id', 'role', 'status', 'joined_at')
            ->wherePivot('status', 'active');
    }

    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('id', 'role', 'jersey_number', 'is_active', 'joined_at')
            ->wherePivot('is_active', true);
    }

    public function availabilityResponses()
    {
        return $this->hasMany(Availability::class);
    }

    public function battingScores()
    {
        return $this->hasMany(BattingScore::class);
    }

    public function bowlingFigures()
    {
        return $this->hasMany(BowlingFigure::class);
    }

    public function wicketsTaken()
    {
        return $this->hasMany(Wicket::class, 'bowler_id');
    }

    public function matchFees()
    {
        return $this->hasMany(MatchFee::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function sentInvitations()
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function isClubOwner(Club $club): bool
    {
        return $this->clubs()
            ->where('club_id', $club->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function isTeamAdmin(Team $team): bool
    {
        return $this->teamMemberships()
            ->where('team_id', $team->id)
            ->whereIn('role', ['captain', 'manager', 'admin'])
            ->where('is_active', true)
            ->exists();
    }

    public function isScorerForMatch(Matchs $match): bool
    {
        return MatchScorer::where('match_id', $match->id)
            ->where('user_id', $this->id)
            ->exists();
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/avatars/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name='
              . urlencode($this->name)
              . '&background=1e3a5f&color=fff';
    }
}
