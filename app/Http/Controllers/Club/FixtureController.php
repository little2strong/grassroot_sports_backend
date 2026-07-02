<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FixtureController extends Controller
{
    use ResolvesClub;

    public function index(Request $request): View
    {
        $club = $this->resolveClub($request);

        $fixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('scheduled_date')
            ->orderByDesc('scheduled_time')
            ->paginate(15)
            ->withQueryString();

        return view('club.fixtures.index', [
            'title' => 'Fixtures',
            'club' => $club,
            'fixtures' => $fixtures,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function create(Request $request): View
    {
        $club = $this->resolveClub($request);

        return view('club.fixtures.create', [
            'title' => 'Schedule Fixture',
            'club' => $club,
            'fixture' => new Fixture([
                'club_plays_home' => true,
                'match_type' => 't20',
                'ball_type' => 'leather',
                'status' => 'draft',
                'is_public' => true,
                'overs_per_innings' => 20,
            ]),
            'teams' => $this->clubTeams($club),
            'venues' => $this->venues(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $validated = $this->validateFixture($request, $club);
        $status = $validated['status'] ?? 'draft';

        Fixture::create(array_merge($this->buildFixturePayload($validated), [
            'club_id' => $club->id,
            'created_by' => $request->user()->id,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]));

        return redirect()
            ->route('club.fixtures.index')
            ->with('success', 'Fixture scheduled successfully.');
    }

    public function edit(Request $request, int $fixture): View|RedirectResponse
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        if ($this->isLocked($record)) {
            return redirect()
                ->route('club.fixtures.index')
                ->with('error', 'This fixture cannot be edited while live or completed.');
        }

        return view('club.fixtures.edit', [
            'title' => 'Edit Fixture',
            'club' => $club,
            'fixture' => $record,
            'teams' => $this->clubTeams($club),
            'venues' => $this->venues(),
        ]);
    }

    public function update(Request $request, int $fixture): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        if ($this->isLocked($record)) {
            return redirect()
                ->route('club.fixtures.index')
                ->with('error', 'This fixture cannot be edited while live or completed.');
        }

        $validated = $this->validateFixture($request, $club, $record);
        $newStatus = $validated['status'] ?? $record->status;

        $payload = $this->buildFixturePayload($validated);
        $payload['status'] = $newStatus;

        if ($newStatus === 'published' && !$record->published_at) {
            $payload['published_at'] = now();
        }

        $record->update($payload);

        return redirect()
            ->route('club.fixtures.index')
            ->with('success', 'Fixture updated successfully.');
    }

    public function destroy(Request $request, int $fixture): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        if ($this->isLocked($record)) {
            return redirect()
                ->route('club.fixtures.index')
                ->with('error', 'Cannot delete a fixture that is live or completed.');
        }

        if ($record->match()->exists()) {
            return redirect()
                ->route('club.fixtures.index')
                ->with('error', 'Cannot delete a fixture that has already started scoring.');
        }

        $record->delete();

        return redirect()
            ->route('club.fixtures.index')
            ->with('success', 'Fixture deleted successfully.');
    }

    private function resolveFixture($club, int $fixtureId): Fixture
    {
        return Fixture::forClub($club->id)
            ->where('id', $fixtureId)
            ->firstOrFail();
    }

    private function isLocked(Fixture $fixture): bool
    {
        return in_array($fixture->status, ['live', 'paused', 'completed'], true);
    }

    private function clubTeams($club)
    {
        return $club->teams()->active()->orderBy('name')->get();
    }

    private function venues()
    {
        return Venue::query()->where('is_active', true)->orderBy('name')->get();
    }

    private function validateFixture(Request $request, $club, ?Fixture $fixture = null): array
    {
        $editableStatuses = ['draft', 'published', 'cancelled', 'postponed'];

        $validated = $request->validate([
            'club_team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($q) => $q->where('club_id', $club->id)),
            ],
            'opponent_name' => 'required|string|max:255',
            'club_plays_home' => 'sometimes|boolean',
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'match_type' => ['required', Rule::in(['t10', 't20', 'odi_50', 'odi_40', 'test', 'custom'])],
            'overs_per_innings' => 'nullable|integer|min:1|max:500',
            'ball_type' => ['required', Rule::in(['leather', 'tennis', 'tape'])],
            'status' => ['required', Rule::in($editableStatuses)],
            'is_public' => 'sometimes|boolean',
        ]);

        $validated['club_plays_home'] = $request->boolean('club_plays_home');
        $validated['is_public'] = $request->boolean('is_public');

        if (empty($validated['overs_per_innings'])) {
            $validated['overs_per_innings'] = $this->defaultOvers($validated['match_type']);
        }

        return $validated;
    }

    private function buildFixturePayload(array $validated): array
    {
        $clubPlaysHome = $validated['club_plays_home'] ?? true;
        $clubTeamId = (int) $validated['club_team_id'];
        $opponentName = trim($validated['opponent_name']);

        return [
            'club_plays_home' => $clubPlaysHome,
            'venue_id' => $validated['venue_id'] ?? null,
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'match_type' => $validated['match_type'],
            'overs_per_innings' => $validated['overs_per_innings'],
            'ball_type' => $validated['ball_type'],
            'is_public' => $validated['is_public'] ?? true,
            'home_team_id' => $clubPlaysHome ? $clubTeamId : null,
            'away_team_id' => $clubPlaysHome ? null : $clubTeamId,
            'home_opponent_name' => $clubPlaysHome ? null : $opponentName,
            'away_opponent_name' => $clubPlaysHome ? $opponentName : null,
        ];
    }

    private function defaultOvers(string $matchType): int
    {
        return match ($matchType) {
            't10' => 10,
            't20' => 20,
            'odi_40' => 40,
            'odi_50' => 50,
            'test' => 90,
            default => 20,
        };
    }
}
