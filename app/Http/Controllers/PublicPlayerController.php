<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPlayerController extends Controller
{
    /**
     * Show the public player profile.
     */
    public function show(User $user): View
    {
        // Ensure relationships are loaded
        $user->load([
            'playerProfile',
            'teams.club',
            'battingScores' => function ($query) {
                $query->with('match')->latest()->take(5);
            },
            'bowlingFigures' => function ($query) {
                $query->with('match')->latest()->take(5);
            }
        ]);

        // Default to not public if profile doesn't exist yet, but for now we'll allow viewing basic details if no profile exists
        $profile = $user->playerProfile;

        // If you want to enforce privacy later, uncomment this:
        // if ($profile && !$profile->is_public_profile) {
        //     abort(403, 'This player profile is private.');
        // }

        return view('public.player.show', [
            'user' => $user,
            'profile' => $profile,
            'recentBatting' => $user->battingScores,
            'recentBowling' => $user->bowlingFigures,
        ]);
    }
}
