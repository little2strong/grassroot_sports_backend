<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Availability;
use App\Models\ClubMember;
use App\Models\Fixture;
use App\Models\MatchFee;
use App\Models\TeamMember;
use App\Models\User;
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
            ->with(['homeTeam', 'awayTeam', 'venue', 'scorer', 'matchFees.player'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('scheduled_date')
            ->orderByDesc('scheduled_time')
            ->paginate(15)
            ->withQueryString();

        $scorers = ClubMember::query()
            ->where('club_id', $club->id)
            ->active()
            // ->whereIn('role', ['owner', 'admin', 'manager', 'scorer', 'captain'])
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn ($u) => $u && $u->user_type === 'player' && $u->is_active)
            ->unique('id')
            ->sortBy(fn ($u) => $u->full_name ?: $u->email)
            ->values();

        return view('club.fixtures.index', [
            'title' => 'Fixtures',
            'club' => $club,
            'fixtures' => $fixtures,
            'scorers' => $scorers,
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

        $fixture = Fixture::create(array_merge($this->buildFixturePayload($validated), [
            'club_id' => $club->id,
            'created_by' => $request->user()->id,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]));

        $members = ClubMember::where('club_id', $club->id)
            ->where('role', 'player')
            ->where('status', 'active')
            ->pluck('user_id');

        foreach ($members as $userId) {
            AppNotification::create([
                'user_id' => $userId,
                'type' => AppNotification::TYPE_FIXTURE_PUBLISHED,
                'title' => 'New fixture scheduled',
                'message' => $fixture->home_display_name . ' vs ' . $fixture->away_display_name . ' is scheduled.',
                'notifiable_type' => Fixture::class,
                'notifiable_id' => $fixture->id,
                'data' => [
                    'fixture_id' => $fixture->id,
                    'club_id' => $club->id,
                    'club_name' => $club->name,
                    'match_type' => $fixture->match_type,
                    'scheduled_date' => $fixture->scheduled_date?->format('Y-m-d'),
                ],
            ]);
        }

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

        if ($newStatus === 'published' && ! $record->published_at) {
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

    public function availability(Request $request, int $fixture): View
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        $teamId = $record->clubTeamId();

        $team = $teamId
            ? $club->teams()->where('id', $teamId)->first()
            : null;

        $members = collect();
        $availabilityByUser = collect();

        if ($team) {
            $members = TeamMember::query()
                ->where('team_id', $team->id)
                ->where('is_active', true)
                ->with(['user.playerProfile'])
                ->orderBy('jersey_number')
                ->get();

            $availabilityByUser = Availability::query()
                ->where('fixture_id', $record->id)
                ->where('team_id', $team->id)
                ->get()
                ->keyBy('user_id');
        }

        $statusFilter = $request->query('status');

        $rows = $members->map(function ($member) use ($availabilityByUser) {
            $availability = $availabilityByUser->get($member->user_id);

            return [
                'member' => $member,
                'availability' => $availability,
                'status' => $availability?->status ?? 'pending',
                'status_label' => $availability?->status_label ?? 'Pending',
                'reason' => $availability?->reason,
                'responded_at' => $availability?->responded_at,
            ];
        });

        if ($statusFilter) {
            $rows = $rows->filter(fn ($row) => $row['status'] === $statusFilter)->values();
        }

        $summary = [
            'total' => $members->count(),
            'available' => $rows->filter(fn ($r) => $r['status'] === 'available')->count(),
            'maybe' => $rows->filter(fn ($r) => $r['status'] === 'maybe')->count(),
            'unavailable' => $rows->filter(fn ($r) => $r['status'] === 'unavailable')->count(),
            'pending' => $rows->filter(fn ($r) => $r['status'] === 'pending')->count(),
        ];

        return view('club.fixtures.availability', [
            'title' => 'Fixture Availability',
            'club' => $club,
            'fixture' => $record,
            'team' => $team,
            'rows' => $rows,
            'summary' => $summary,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function assignScorer(Request $request, int $fixture): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        if ($this->isLocked($record)) {
            return redirect()
                ->route('club.fixtures.index')
                ->with('error', 'Cannot change scorer while match is live or completed.');
        }

        $validated = $request->validate([
            'scorer_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('user_type', 'player')->where('is_active', true)),
            ],
        ]);

        $scorerId = $validated['scorer_user_id'] ?? null;

        if ($scorerId) {
            $allowed = ClubMember::query()
                ->where('club_id', $club->id)
                ->active()
                ->where('user_id', $scorerId)
                // ->whereIn('role', ['owner', 'admin', 'manager', 'scorer', 'captain'])
                ->exists();

            if (! $allowed) {
                return back()->with('error', 'Selected scorer is not allowed for your club.');
            }
        }

        $record->update([
            'scorer_user_id' => $scorerId,
            'scorer_assigned_at' => $scorerId ? now() : null,
        ]);

        $name = $scorerId ? (User::find($scorerId)?->full_name ?: 'Scorer') : 'No scorer';

        return redirect()
            ->route('club.fixtures.index')
            ->with('success', "Scorer updated: {$name}.");
    }

    public function showCollectFee(Request $request, int $fixture): View
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        $teamId = $record->clubTeamId();
        $team = $teamId
            ? $club->teams()->where('id', $teamId)->first()
            : null;

        $members = collect();
        if ($team) {
            $members = TeamMember::query()
                ->where('team_id', $team->id)
                ->where('is_active', true)
                ->with('user.playerProfile')
                ->orderBy('jersey_number')
                ->get();
        }

        $existingFees = MatchFee::query()
            ->where('fixture_id', $record->id)
            ->with('player')
            ->get();

        return view('club.fixtures.collect-fee', [
            'title' => 'Collect Fee',
            'club' => $club,
            'fixture' => $record,
            'team' => $team,
            'members' => $members,
            'existingFees' => $existingFees,
        ]);
    }

    public function collectFee(Request $request, int $fixture): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        $teamId = $record->clubTeamId();
        $team = $teamId
            ? $club->teams()->where('id', $teamId)->first()
            : null;

        $validated = $request->validate([
            'player_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('user_type', 'player')->where('is_active', true)),
            ],
            'amount' => 'required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:3',
            'notes' => 'sometimes|nullable|string|max:1000',
            'payment_reference' => 'sometimes|nullable|string|max:255',
        ]);

        $player = User::where('user_type', 'player')->where('is_active', true)->find($validated['player_id']);

        if (! $player) {
            return redirect()
                ->route('club.fixtures.index')
                ->with('error', 'Player not found or not active.');
        }

        MatchFee::create([
            'fixture_id' => $record->id,
            'team_id' => $team?->id,
            'user_id' => $player->id,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'USD',
            'status' => 'verified',
            'due_date' => now()->addDays(7),
            'notes' => $validated['notes'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'assigned_by' => $request->user()->id,
            'paid_by_player_at' => now(),
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return redirect()
            ->route('club.fixtures.index')
            ->with('success', "Fee collected from {$player->name} successfully.");
    }

    public function showBulkCollectFee(Request $request, int $fixture): View
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        $teamId = $record->clubTeamId();
        $team = $teamId
            ? $club->teams()->where('id', $teamId)->first()
            : null;

        $members = collect();
        if ($team) {
            $members = TeamMember::query()
                ->where('team_id', $team->id)
                ->where('is_active', true)
                ->with('user.playerProfile')
                ->orderBy('jersey_number')
                ->get();
        }

        $existingFees = MatchFee::query()
            ->where('fixture_id', $record->id)
            ->with('player')
            ->get()
            ->keyBy('user_id');

        $existingFeesList = $existingFees->values();

        return view('club.fixtures.bulk-collect-fee', [
            'title' => 'Bulk Collect Fee',
            'club' => $club,
            'fixture' => $record,
            'team' => $team,
            'members' => $members,
            'existingFees' => $existingFeesList,
            'existingFeesByPlayer' => $existingFees,
        ]);
    }

    public function bulkCollectFee(Request $request, int $fixture): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $record = $this->resolveFixture($club, $fixture);

        $teamId = $record->clubTeamId();
        $team = $teamId
            ? $club->teams()->where('id', $teamId)->first()
            : null;

        $members = collect();
        if ($team) {
            $members = TeamMember::query()
                ->where('team_id', $team->id)
                ->where('is_active', true)
                ->with('user.playerProfile')
                ->orderBy('jersey_number')
                ->get();
        }

        $validated = $request->validate([
            'players' => 'sometimes|array',
            'players.*.player_id' => 'required|integer',
            'players.*.amount' => 'required|numeric|min:0',
            'collect_all' => 'sometimes|boolean',
            'all_amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:3',
            'payment_reference' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        $currency = $validated['currency'] ?? 'USD';
        $paymentReference = $validated['payment_reference'] ?? null;
        $notes = $validated['notes'] ?? null;

        $collectedCount = 0;
        $skippedCount = 0;
        $skippedPlayers = [];

        $existingFees = MatchFee::query()
            ->where('fixture_id', $record->id)
            ->pluck('user_id')
            ->toArray();

        if (! empty($validated['collect_all'])) {
            $allAmount = $validated['all_amount'] ?? 0;
            foreach ($members as $member) {
                if (in_array($member->user->id, $existingFees)) {
                    continue;
                }

                MatchFee::create([
                    'fixture_id' => $record->id,
                    'team_id' => $team?->id,
                    'user_id' => $member->user->id,
                    'amount' => $allAmount,
                    'currency' => $currency,
                    'status' => 'verified',
                    'due_date' => now()->addDays(7),
                    'notes' => $notes,
                    'payment_reference' => $paymentReference,
                    'assigned_by' => $request->user()->id,
                    'paid_by_player_at' => now(),
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ]);

                $collectedCount++;
            }
        } else {
            foreach ($validated['players'] ?? [] as $playerId => $playerData) {
                $player = User::where('user_type', 'player')->where('is_active', true)->find($playerData['player_id']);

                if (! $player) {
                    $skippedCount++;
                    $skippedPlayers[] = "Player ID {$playerData['player_id']} not found";

                    continue;
                }

                if (in_array($player->id, $existingFees)) {
                    $skippedCount++;
                    $skippedPlayers[] = "Player {$player->name} already has a fee collected";

                    continue;
                }

                MatchFee::create([
                    'fixture_id' => $record->id,
                    'team_id' => $team?->id,
                    'user_id' => $player->id,
                    'amount' => $playerData['amount'],
                    'currency' => $currency,
                    'status' => 'verified',
                    'due_date' => now()->addDays(7),
                    'notes' => $notes,
                    'payment_reference' => $paymentReference,
                    'assigned_by' => $request->user()->id,
                    'paid_by_player_at' => now(),
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ]);

                $collectedCount++;
            }
        }

        $message = "Fees collected for {$collectedCount} player(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} player(s) skipped.";
        }

        return redirect()
            ->route('club.fixtures.index')
            ->with('success', $message);
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
