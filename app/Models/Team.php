<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id', 'name', 'slug', 'short_name', 'logo',
        'primary_color', 'secondary_color', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function players()
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('id', 'role', 'jersey_number', 'is_active', 'joined_at')
            ->wherePivot('is_active', true);
    }

    public function activePlayers()
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('role', 'jersey_number')
            ->wherePivot('is_active', true)
            ->wherePivot('role', 'player');
    }

    public function homeFixtures()
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    public function awayFixtures()
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }

    public function allFixtures()
    {
        return Fixture::where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/teams/' . $this->logo) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function playerCount(): int
    {
        return $this->members()->where('is_active', true)->where('role', 'player')->count();
    }
}
