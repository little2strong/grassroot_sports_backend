<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'address', 'city', 'country',
        'latitude', 'longitude', 'number_of_pitches',
        'has_floodlights', 'notes', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'number_of_pitches' => 'integer',
        'has_floodlights' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function fixtures()
    {
        return $this->hasMany(Fixture::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address, $this->city, $this->country])
            ->filter()
            ->join(', ');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }
}
