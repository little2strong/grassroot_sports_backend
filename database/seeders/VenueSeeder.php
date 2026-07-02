<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Lords Cricket Ground',
                'address' => 'St Johns Wood Road',
                'city' => 'London',
                'country' => 'United Kingdom',
                'latitude' => 51.5294,
                'longitude' => -0.1727,
                'number_of_pitches' => 1,
                'has_floodlights' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Old Trafford',
                'address' => 'Talbot Road',
                'city' => 'Manchester',
                'country' => 'United Kingdom',
                'latitude' => 53.4631,
                'longitude' => -2.2914,
                'number_of_pitches' => 2,
                'has_floodlights' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Edgbaston Cricket Ground',
                'address' => 'Edgbaston Road',
                'city' => 'Birmingham',
                'country' => 'United Kingdom',
                'latitude' => 52.4558,
                'longitude' => -1.9025,
                'number_of_pitches' => 2,
                'has_floodlights' => true,
                'is_active' => true,
            ],
            [
                'name' => 'The Oval',
                'address' => 'Kennington Oval',
                'city' => 'London',
                'country' => 'United Kingdom',
                'latitude' => 51.4839,
                'longitude' => -0.1140,
                'number_of_pitches' => 1,
                'has_floodlights' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Melbourne Cricket Ground',
                'address' => 'Brunton Avenue',
                'city' => 'Melbourne',
                'country' => 'Australia',
                'latitude' => -37.8199,
                'longitude' => 144.9834,
                'number_of_pitches' => 2,
                'has_floodlights' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Riverside Community Ground',
                'address' => 'Deansgate Lane',
                'city' => 'Manchester',
                'country' => 'United Kingdom',
                'latitude' => 53.4808,
                'longitude' => -2.2426,
                'number_of_pitches' => 3,
                'has_floodlights' => false,
                'notes' => 'Local club matches and practice sessions.',
                'is_active' => true,
            ],
        ];

        foreach ($venues as $venue) {
            Venue::updateOrCreate(
                ['name' => $venue['name'], 'city' => $venue['city']],
                $venue
            );
        }
    }
}
