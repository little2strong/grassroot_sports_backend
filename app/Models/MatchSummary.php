<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id', 'match_id',
        'innings_one', 'innings_two', 'key_stats',
        'narrative', 'is_published', 'published_at',
    ];

    protected $casts = [
        'innings_one' => 'array',
        'innings_two' => 'array',
        'key_stats' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function match()
    {
        return $this->belongsTo(Matchs::class);
    }

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function getHighestScoreAttribute(): ?array
    {
        return $this->key_stats['highest_score'] ?? null;
    }

    public function getBestBowlingAttribute(): ?array
    {
        return $this->key_stats['best_bowling'] ?? null;
    }

    public function getManOfTheMatchAttribute(): ?array
    {
        return $this->key_stats['man_of_the_match'] ?? null;
    }

    public function getTotalRunsAttribute(): int
    {
        $inn1 = $this->innings_one['runs'] ?? 0;
        $inn2 = $this->innings_two['runs'] ?? 0;
        return $inn1 + $inn2;
    }

    public function getTotalWicketsAttribute(): int
    {
        $inn1 = $this->innings_one['wickets'] ?? 0;
        $inn2 = $this->innings_two['wickets'] ?? 0;
        return $inn1 + $inn2;
    }
}
