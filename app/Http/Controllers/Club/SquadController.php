<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
