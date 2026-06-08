<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'guest_identifier',
        'club_id', 'fixture_id', 'team_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeForClub($query, int $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    public function scopeForFixture($query, int $fixtureId)
    {
        return $query->where('fixture_id', $fixtureId);
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeGuests($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeRegistered($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function isGuest(): bool
    {
        return is_null($this->user_id);
    }
}
