<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Invitation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $club = $user->ownedClub()
            ->withCount(['teams', 'members', 'fixtures'])
            ->first();

        $title = 'Dashboard';

        $stats = [
            'teams' => $club->teams_count,
            'members' => $club->members_count,
            'fixtures_total' => $club->fixtures_count,
            'fixtures_upcoming' => Fixture::forClub($club->id)
                ->whereIn('status', ['published', 'draft'])
                ->where('scheduled_date', '>=', now()->toDateString())
                ->count(),
            'fixtures_live' => Fixture::forClub($club->id)
                ->whereIn('status', ['live', 'paused'])
                ->count(),
            'invitations_pending' => Invitation::where('club_id', $club->id)
                ->where('status', 'pending')
                ->count(),
        ];

        $upcomingFixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->whereIn('status', ['published', 'draft'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        $liveFixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->whereIn('status', ['live', 'paused'])
            ->get();

        return view('club.dashboard', compact('user', 'club', 'title', 'stats', 'upcomingFixtures', 'liveFixtures'));
    }
}
