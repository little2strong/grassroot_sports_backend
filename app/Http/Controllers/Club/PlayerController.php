<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    use ResolvesClub;

    public function index(Request $request)
    {
        $club = $this->resolveClub($request);

        $members = $club->members()
            ->with('user.playerProfile')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status), fn ($q) => $q->active())
            ->orderByDesc('joined_at')
            ->paginate(15)
            ->withQueryString();

        return view('club.players.index', [
            'title' => 'Players',
            'club' => $club,
            'members' => $members,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function show(Request $request, User $player): View
    {
        $club = $this->resolveClub($request);

        $membership = $club->members()
            ->where('user_id', $player->id)
            ->first();

        if (!$membership) {
            abort(404);
        }

        $player->load([
            'playerProfile',
            'teams' => fn ($q) => $q->where('teams.club_id', $club->id),
        ]);

        $recentAvailability = Availability::query()
            ->where('user_id', $player->id)
            ->whereIn('fixture_id', $club->fixtures()->select('id'))
            ->with('fixture')
            ->latest()
            ->limit(10)
            ->get();

        $playerClubs = \App\Models\ClubMember::where('user_id', $player->id)
            ->with('club')
            ->get()
            ->map(function ($member) {
                return [
                    'club' => $member->club,
                    'role' => $member->role,
                    'status' => $member->status,
                    'joined_at' => $member->joined_at,
                ];
            })
            ->values();

        $stats = [
            'total_matches' => $player->playerProfile?->total_matches ?? 0,
            'total_runs' => $player->playerProfile?->total_runs ?? 0,
            'total_wickets' => $player->playerProfile?->total_wickets ?? 0,
            'highest_score' => $player->playerProfile?->highest_score ?? 0,
            'total_fifties' => $player->playerProfile?->total_fifties ?? 0,
            'total_hundreds' => $player->playerProfile?->total_hundreds ?? 0,
            'total_five_wickets' => $player->playerProfile?->total_five_wickets ?? 0,
        ];

        return view('club.players.show', [
            'title' => 'Player Details',
            'club' => $club,
            'player' => $player,
            'membership' => $membership,
            'recentAvailability' => $recentAvailability,
            'stats' => $stats,
            'playerClubs' => $playerClubs,
        ]);
    }
}
