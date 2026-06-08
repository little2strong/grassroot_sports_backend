<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Club extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'logo', 'cover_image',
        'description', 'country', 'city', 'address', 'website',
        'founded_year', 'is_public', 'is_verified',
        'hide_player_names_publicly', 'hide_player_photos_publicly',
        'show_public_profiles',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_verified' => 'boolean',
        'hide_player_names_publicly' => 'boolean',
        'hide_player_photos_publicly' => 'boolean',
        'show_public_profiles' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Club $club) {
            if (empty($club->slug)) {
                $club->slug = Str::slug($club->name) . '-' . Str::random(6);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(ClubMember::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function fixtures()
    {
        return $this->hasMany(Fixture::class);
    }

    public function followers()
    {
        return $this->hasMany(Follower::class);
    }

    public function imports()
    {
        return $this->hasMany(FixtureImport::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/clubs/' . $this->logo) : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? asset('storage/clubs/covers/' . $this->cover_image) : null;
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeInCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }
}
