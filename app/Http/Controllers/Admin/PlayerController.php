<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Manage Players';

        $query = User::query()
            ->where('user_type', 'player')
            ->with(['playerProfile', 'clubs'])
            ->withCount('clubs');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('primary_role')) {
            $query->whereHas('playerProfile', fn ($q) => $q->where('primary_role', $request->primary_role));
        }

        if ($request->filled('is_onboarded')) {
            $query->where('is_onboarded', $request->boolean('is_onboarded'));
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'first_name', 'last_name', 'email', 'is_active'];

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $players = $query->orderBy($sortBy, $sortDir)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $summary = [
            'total' => User::where('user_type', 'player')->count(),
            'active' => User::where('user_type', 'player')->where('is_active', true)->count(),
            'onboarded' => User::where('user_type', 'player')->where('is_onboarded', true)->count(),
        ];

        return view('admin.player.index', compact('players', 'title', 'summary'));
    }

    public function show(User $player)
    {
        if ($player->user_type !== 'player') {
            return redirect()->route('admin.players.index')->with('error', 'This user is not a player account.');
        }

        $title = $player->name;

        $player->load([
            'playerProfile',
            'clubMemberships.club',
            'clubs',
            'teams.club',
            'availabilityResponses' => fn ($q) => $q->with('fixture')->latest()->limit(10),
        ]);

        $stats = [
            'clubs' => $player->clubs->count(),
            'teams' => $player->teams->count(),
            'total_matches' => $player->playerProfile?->total_matches ?? 0,
            'total_runs' => $player->playerProfile?->total_runs ?? 0,
            'total_wickets' => $player->playerProfile?->total_wickets ?? 0,
        ];

        return view('admin.player.show', compact('player', 'title', 'stats'));
    }

    public function toggleActive(User $player): RedirectResponse
    {
        if ($player->user_type !== 'player') {
            return back()->with('error', 'Only player accounts can be updated.');
        }

        $player->update(['is_active' => !$player->is_active]);

        $name = trim($player->first_name . ' ' . $player->last_name) ?: $player->email;

        return back()->with('success', $player->is_active
            ? "{$name} is now active."
            : "{$name} has been deactivated.");
    }
}
