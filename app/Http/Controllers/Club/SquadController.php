<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SquadController extends Controller
{
    use ResolvesClub;

    public function index(Request $request): View
    {
        $club = $this->resolveClub($request);

        $teams = $club->teams()
            ->withCount('players')
            ->orderBy('name')
            ->paginate(15);

        return view('club.squads.index', [
            'title' => 'Squads',
            'club' => $club,
            'teams' => $teams,
        ]);
    }

    public function create(Request $request): View
    {
        return view('club.squads.create', [
            'title' => 'Add Squad',
            'club' => $this->resolveClub($request),
            'team' => new Team([
                'primary_color' => '#1e3a5f',
                'secondary_color' => '#ffffff',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $validated = $this->validateTeam($request);

        $club->teams()->create(array_merge($validated, [
            'slug' => $this->uniqueSlug($club->id, $validated['name']),
            'primary_color' => $validated['primary_color'] ?? '#1e3a5f',
            'secondary_color' => $validated['secondary_color'] ?? '#ffffff',
        ]));

        return redirect()
            ->route('club.squads.index')
            ->with('success', 'Squad created successfully.');
    }

    public function edit(Request $request, int $team): View
    {
        $squad = $this->resolveTeam($request, $team);

        return view('club.squads.edit', [
            'title' => 'Edit Squad',
            'club' => $this->resolveClub($request),
            'team' => $squad,
        ]);
    }

    public function update(Request $request, int $team): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $squad = $this->resolveTeam($request, $team);
        $validated = $this->validateTeam($request);

        if ($squad->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug($club->id, $validated['name'], $squad->id);
        }

        $squad->update($validated);

        return redirect()
            ->route('club.squads.index')
            ->with('success', 'Squad updated successfully.');
    }

    public function destroy(Request $request, int $team): RedirectResponse
    {
        $squad = $this->resolveTeam($request, $team);

        $hasFixtures = Fixture::query()
            ->where(function ($q) use ($squad) {
                $q->where('home_team_id', $squad->id)
                    ->orWhere('away_team_id', $squad->id);
            })
            ->exists();

        if ($hasFixtures) {
            return redirect()
                ->route('club.squads.index')
                ->with('error', 'Cannot delete this squad — it is linked to one or more fixtures.');
        }

        $squad->delete();

        return redirect()
            ->route('club.squads.index')
            ->with('success', 'Squad deleted successfully.');
    }

    public function players(Request $request, int $team): View
    {
        $club = $this->resolveClub($request);
        $squad = $this->resolveTeam($request, $team);

        $members = TeamMember::query()
            ->where('team_id', $squad->id)
            ->where('is_active', true)
            ->with(['user.playerProfile'])
            ->orderByRaw("CASE role WHEN 'captain' THEN 0 WHEN 'manager' THEN 1 WHEN 'scorer' THEN 2 ELSE 3 END")
            ->orderBy('jersey_number')
            ->get();

        $memberUserIds = $members->pluck('user_id');

        $availablePlayers = $club->members()
            ->active()
            ->where('role', 'player')
            ->whereHas('user', fn ($q) => $q->where('user_type', 'player')->where('is_active', true))
            ->whereNotIn('user_id', $memberUserIds)
            ->with(['user.playerProfile'])
            ->orderByDesc('joined_at')
            ->get()
            ->map(fn ($m) => $m->user)
            ->values();

        $otherTeams = $club->teams()
            ->where('id', '!=', $squad->id)
            ->orderBy('name')
            ->get();

        return view('club.squads.players', [
            'title' => 'Squad Players',
            'club' => $club,
            'team' => $squad,
            'members' => $members,
            'availablePlayers' => $availablePlayers,
            'otherTeams' => $otherTeams,
        ]);
    }

    public function addPlayer(Request $request, int $team): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $squad = $this->resolveTeam($request, $team);

        $validated = $request->validate([
            'player_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('user_type', 'player')->where('is_active', true)),
            ],
            'role' => ['required', Rule::in(['player', 'captain', 'manager', 'scorer'])],
            'jersey_number' => 'nullable|integer|min:0|max:999',
            'move_from_other_teams' => 'sometimes|boolean',
        ]);

        $player = User::query()
            ->where('user_type', 'player')
            ->where('is_active', true)
            ->findOrFail((int) $validated['player_id']);

        $isClubMember = $club->members()->active()->where('user_id', $player->id)->exists();
        if (!$isClubMember) {
            return back()->with('error', 'This player is not an active member of your club.');
        }

        $move = $request->has('move_from_other_teams') ? $request->boolean('move_from_other_teams') : true;

        if ($move) {
            $clubTeamIds = $club->teams()->pluck('id');
            TeamMember::query()
                ->where('user_id', $player->id)
                ->whereIn('team_id', $clubTeamIds)
                ->where('team_id', '!=', $squad->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        TeamMember::updateOrCreate(
            ['team_id' => $squad->id, 'user_id' => $player->id],
            [
                'role' => $validated['role'],
                'jersey_number' => $validated['jersey_number'] ?? null,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        return redirect()
            ->route('club.squads.players', $squad)
            ->with('success', 'Player added to squad.');
    }

    public function removePlayer(Request $request, int $team, int $user): RedirectResponse
    {
        $squad = $this->resolveTeam($request, $team);

        $member = TeamMember::query()
            ->where('team_id', $squad->id)
            ->where('user_id', $user)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return back()->with('error', 'Player is not an active member of this squad.');
        }

        $member->update(['is_active' => false]);

        return redirect()
            ->route('club.squads.players', $squad)
            ->with('success', 'Player removed from squad.');
    }

    public function movePlayer(Request $request, int $team, int $user): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $fromTeam = $this->resolveTeam($request, $team);

        $validated = $request->validate([
            'to_team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($q) => $q->where('club_id', $club->id)),
            ],
        ]);

        $toTeamId = (int) $validated['to_team_id'];
        if ($toTeamId === $fromTeam->id) {
            return back()->with('error', 'Select a different squad to move this player.');
        }

        $member = TeamMember::query()
            ->where('team_id', $fromTeam->id)
            ->where('user_id', $user)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return back()->with('error', 'Player is not an active member of this squad.');
        }

        TeamMember::query()
            ->where('user_id', $user)
            ->whereIn('team_id', $club->teams()->pluck('id'))
            ->where('is_active', true)
            ->update(['is_active' => false]);

        TeamMember::updateOrCreate(
            ['team_id' => $toTeamId, 'user_id' => $user],
            [
                'role' => $member->role,
                'jersey_number' => $member->jersey_number,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        return redirect()
            ->route('club.squads.players', $toTeamId)
            ->with('success', 'Player moved successfully.');
    }

    private function resolveTeam(Request $request, int $teamId): Team
    {
        return $this->resolveClub($request)
            ->teams()
            ->where('id', $teamId)
            ->firstOrFail();
    }

    private function validateTeam(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:10',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function uniqueSlug(int $clubId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'squad';
        $slug = $base;
        $counter = 1;

        while (
            Team::withTrashed()
                ->where('club_id', $clubId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
