<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'user_id', 'role', 'jersey_number', 'is_active', 'joined_at',
    ];

    protected $casts = [
        'jersey_number' => 'integer',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCaptains($query)
    {
        return $query->where('role', 'captain');
    }

    public function scopePlayers($query)
    {
        return $query->where('role', 'player');
    }

    public function isCaptain(): bool
    {
        return $this->role === 'captain';
    }

    public function isScorer(): bool
    {
        return $this->role === 'scorer';
    }

    public function canManage(): bool
    {
        return in_array($this->role, ['captain', 'manager']);
    }
}
