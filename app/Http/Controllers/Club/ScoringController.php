<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Models\Fixture;
use Illuminate\Http\Request;

class ScoringController extends Controller
{
    use ResolvesClub;

    public function index(Request $request)
    {
        $club = $this->resolveClub($request);

        $liveFixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue', 'match'])
            ->whereIn('status', ['live', 'paused'])
            ->orderByDesc('started_at')
            ->get();

        $readyFixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->where('status', 'published')
            ->whereNotNull('scorer_user_id')
            ->whereDoesntHave('match')
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        return view('club.scoring.index', [
            'title' => 'Scoring',
            'club' => $club,
            'liveFixtures' => $liveFixtures,
            'readyFixtures' => $readyFixtures,
        ]);
    }
}
