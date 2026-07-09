<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\ClubMember;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\FixtureImport;
use App\Models\Squad;
use App\Models\TeamMember;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClubController extends Controller
{
    public function squads(Request $request, ?int $clubId = null): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$clubId && $user && $user->user_type === 'club') {
            $club = $user->ownedClub()->first();
        } elseif ($clubId) {
            $club = Club::query()->find($clubId);
        } else {
            return response()->json(['message' => 'Club id required'], 422);
        }

        if (!$club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $teams = Team::query()->where('club_id', $club->id)->orderBy('name')->get();

        $data = $teams->map(function (Team $team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'short_name' => $team->short_name,
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'is_active' => (bool)$team->is_active,
                'player_count' => $team->playerCount(),
            ];
        });

        return response()->json(['data' => ['club' => ['id' => $club->id, 'name' => $club->name], 'squads' => $data]]);
    }

    public function squadPlayers(Request $request, int $teamId): JsonResponse
    {
        $team = Team::with(['players' => function ($q) {
            $q->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->with('playerProfile');
        }])->find($teamId);

        if (!$team) {
            return response()->json(['message' => 'Squad not found'], 404);
        }

        $players = $team->players->map(function ($user) {
            $profile = $user->playerProfile;
            $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return [
                'id' => $user->id,
                'name' => $displayName ?: $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'primary_role' => $profile->primary_role ?? null,
                'batting_style' => $profile->batting_style ?? null,
                'bowling_style' => $profile->bowling_style ?? null,
                'role_label' => $profile?->role_label,
                'batting_style_label' => $profile?->batting_style_label,
                'bowling_style_label' => $profile?->bowling_style_label,
                'role' => $user->pivot->role ?? 'player',
                'jersey_number' => $user->pivot->jersey_number,
                'joined_at' => optional($user->pivot->joined_at)->toIso8601String(),
            ];
        });

        $roleCounts = $team->players->reduce(function ($counts, $user) {
            $role = $user->playerProfile?->primary_role;

            if ($role === 'batsman') {
                $counts['batsman']++;
            } elseif ($role === 'bowler') {
                $counts['bowler']++;
            } elseif ($role === 'wicket_keeper') {
                $counts['wicket_keeper']++;
            } elseif ($role === 'all_rounder') {
                $counts['all_rounder']++;
            }

            return $counts;
        }, ['batsman' => 0, 'bowler' => 0, 'wicket_keeper' => 0, 'all_rounder' => 0]);

        return response()->json([
            'data' => [
                'team' => ['id' => $team->id, 'name' => $team->name],
                'players' => $players,
                'counts' => $roleCounts,
            ],
        ]);
    }

    public function listFixtures(Request $request, int $clubId): JsonResponse
    {
        $club = $this->resolveClub($request, $clubId);

        if (!$club) {
            return response()->json(['message' => 'Club not found or access denied.'], 404);
        }

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['draft', 'published', 'live', 'paused', 'completed', 'abandoned', 'cancelled', 'postponed'])],
            'upcoming' => 'sometimes|boolean',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after_or_equal:from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Fixture::query()
            ->forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue', 'scorer', 'squads.player'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        if (!empty($validated['from'])) {
            $query->where('scheduled_date', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->where('scheduled_date', '<=', $validated['to']);
        }

        $fixtures = $query->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'message' => 'Match schedule fetched successfully.',
            'data' => [
                'fixtures' => $fixtures->through(fn (Fixture $fixture) => $this->formatFixture($fixture)),
            ],
        ]);
    }

    public function showFixture(Request $request, int $clubId, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveFixture($request, $clubId, $fixtureId);

        if (!$fixture) {
            return response()->json(['message' => 'Fixture not found or access denied.'], 404);
        }

        $fixture->load(['homeTeam', 'awayTeam', 'venue', 'match', 'scorer', 'squads.player']);

        return response()->json([
            'message' => 'Fixture fetched successfully.',
            'data' => ['fixture' => $this->formatFixture($fixture)],
        ]);
    }

    public function createFixture(Request $request, int $clubId): JsonResponse
    {
        // dd($request->all());
        $club = $this->resolveClub($request, $clubId);

        if (!$club) {
            return response()->json(['message' => 'Club not found or access denied.'], 404);
        }

        $validated = $this->validateFixtureCreatePayload($request);
        $payload = $this->buildFixtureFromCreatePayload($validated);

        $status = $payload['status'] ?? 'draft';

        $fixture = Fixture::create(array_merge($payload, [
            'club_id' => $club->id,
            'created_by' => auth('sanctum')->id(),
            'status' => $status,
            'is_public' => $payload['is_public'] ?? true,
            'published_at' => $status === 'published' ? now() : null,
        ]));

        $fixture->load(['homeTeam', 'awayTeam', 'venue', 'scorer', 'squads.player']);

        return response()->json([
            'message' => 'Match scheduled successfully.',
            'data' => ['fixture' => $this->formatFixture($fixture)],
        ], 201);
    }

    public function updateFixture(Request $request, int $clubId, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveFixture($request, $clubId, $fixtureId);

        if (!$fixture) {
            return response()->json(['message' => 'Fixture not found or access denied.'], 404);
        }

        if (in_array($fixture->status, ['live', 'paused', 'completed'], true)) {
            return response()->json(['message' => 'Cannot update a fixture that is live or completed.'], 422);
        }

        $club = $this->resolveClub($request, $clubId);
        $validated = $this->validateFixtureSchedulePayload($request, $fixture);

        $newStatus = $validated['status'] ?? $fixture->status;

        if ($newStatus === 'published' && !$fixture->published_at) {
            $validated['published_at'] = now();
        }

        $fixture->update($validated);
        $fixture->load(['homeTeam', 'awayTeam', 'venue', 'scorer', 'squads.player']);

        return response()->json([
            'message' => 'Match schedule updated successfully.',
            'data' => ['fixture' => $this->formatFixture($fixture)],
        ]);
    }

    public function listFixtureAvailability(Request $request, int $clubId, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveFixture($request, $clubId, $fixtureId);

        if (!$fixture) {
            return response()->json(['message' => 'Fixture not found or access denied.'], 404);
        }

        $club = $this->resolveClub($request, $clubId);

        $validated = $request->validate([
            'team_id' => [
                'sometimes',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($query) => $query->where('club_id', $club->id)),
            ],
            'status' => ['sometimes', Rule::in(['available', 'maybe', 'unavailable', 'pending'])],
        ]);

        $teamId = $validated['team_id'] ?? $fixture->clubTeamId();

        if (!$teamId) {
            return response()->json(['message' => 'Select a club squad for this fixture first.'], 422);
        }

        $team = Team::query()->where('id', $teamId)->where('club_id', $club->id)->first();

        if (!$team) {
            return response()->json(['message' => 'Club team not found.'], 404);
        }

        $members = TeamMember::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->with(['user.playerProfile'])
            ->get();

        $availabilityByUser = Availability::query()
            ->where('fixture_id', $fixture->id)
            ->where('team_id', $team->id)
            ->get()
            ->keyBy('user_id');

        $players = $members->map(function (TeamMember $member) use ($availabilityByUser) {
            $user = $member->user;
            $availability = $availabilityByUser->get($member->user_id);
            $fullName = trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? ''));

            return [
                'user_id' => $member->user_id,
                'name' => $fullName ?: $user?->email,
                'email' => $user?->email,
                'primary_role' => $user?->playerProfile?->primary_role,
                'role_label' => $user?->playerProfile?->role_label,
                'team_role' => $member->role,
                'jersey_number' => $member->jersey_number,
                'availability' => $availability ? [
                    'status' => $availability->status,
                    'status_label' => $availability->status_label,
                    'reason' => $availability->reason,
                    'responded_at' => $availability->responded_at?->toIso8601String(),
                ] : [
                    'status' => 'pending',
                    'status_label' => 'Pending',
                    'reason' => null,
                    'responded_at' => null,
                ],
            ];
        });

        if (!empty($validated['status'])) {
            $players = $players->filter(function (array $player) use ($validated) {
                return $player['availability']['status'] === $validated['status'];
            })->values();
        }

        $allPlayers = $members->map(function (TeamMember $member) use ($availabilityByUser) {
            $availability = $availabilityByUser->get($member->user_id);

            return $availability?->status ?? 'pending';
        });

        return response()->json([
            'message' => 'Fixture availability fetched successfully.',
            'data' => [
                'fixture' => [
                    'id' => $fixture->id,
                    'scheduled_date' => $fixture->scheduled_date?->toDateString(),
                    'scheduled_time' => $fixture->scheduled_time?->format('H:i'),
                    'opponent_name' => $fixture->opponentName(),
                ],
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'short_name' => $team->short_name,
                ],
                'summary' => [
                    'total' => $members->count(),
                    'available' => $allPlayers->filter(fn ($status) => $status === 'available')->count(),
                    'maybe' => $allPlayers->filter(fn ($status) => $status === 'maybe')->count(),
                    'unavailable' => $allPlayers->filter(fn ($status) => $status === 'unavailable')->count(),
                    'pending' => $allPlayers->filter(fn ($status) => $status === 'pending')->count(),
                ],
                'players' => $players->values(),
            ],
        ]);
    }

    public function setFixtureClubSquad(Request $request, int $clubId, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveFixture($request, $clubId, $fixtureId);

        if (!$fixture) {
            return response()->json(['message' => 'Fixture not found or access denied.'], 404);
        }

        if (in_array($fixture->status, ['live', 'paused', 'completed'], true)) {
            return response()->json(['message' => 'Cannot change squad after the match is live or completed.'], 422);
        }

        $club = $this->resolveClub($request, $clubId);

        $validated = $request->validate([
            'team_id' => [
                'sometimes',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($query) => $query->where('club_id', $club->id)),
            ],
            'players' => 'required|array|min:1|max:25',
            'players.*.user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'players.*.position' => ['sometimes', Rule::in(['playing_xi', 'reserve', 'twelfth_man'])],
            'players.*.jersey_number' => 'sometimes|nullable|integer|min:0',
            'players.*.is_captain' => 'sometimes|boolean',
            'players.*.is_wicket_keeper' => 'sometimes|boolean',
            'allow_unavailable' => 'sometimes|boolean',
            'allow_maybe' => 'sometimes|boolean',
        ]);

        $teamId = $validated['team_id'] ?? $fixture->clubTeamId();
        $allowUnavailable = $request->boolean('allow_unavailable');
        $allowMaybe = $request->has('allow_maybe') ? $request->boolean('allow_maybe') : true;

        $team = Team::query()->where('id', $teamId)->where('club_id', $club->id)->first();

        if (!$team) {
            return response()->json(['message' => 'Club team not found.'], 404);
        }

        $playerIds = collect($validated['players'])->pluck('user_id')->map(fn ($id) => (int) $id);

        if ($playerIds->duplicates()->isNotEmpty()) {
            return response()->json(['message' => 'Each player can only be added once to the match squad.'], 422);
        }

        $captainCount = collect($validated['players'])->filter(fn ($player) => (bool) ($player['is_captain'] ?? false))->count();
        $keeperCount = collect($validated['players'])->filter(fn ($player) => (bool) ($player['is_wicket_keeper'] ?? false))->count();

        if ($captainCount > 1) {
            return response()->json(['message' => 'Only one captain can be selected.'], 422);
        }

        if ($keeperCount > 1) {
            return response()->json(['message' => 'Only one wicket keeper can be selected.'], 422);
        }

        $availabilityByUser = Availability::query()
            ->where('fixture_id', $fixture->id)
            ->where('team_id', $team->id)
            ->whereIn('user_id', $playerIds)
            ->get()
            ->keyBy('user_id');

        $addedById = auth('sanctum')->id();

        $players = collect($validated['players'])->map(function (array $player) use (
            $team,
            $fixture,
            $addedById,
            $availabilityByUser,
            $allowUnavailable,
            $allowMaybe
        ) {
            $userId = (int) $player['user_id'];

            $teamMember = TeamMember::query()
                ->where('team_id', $team->id)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if (!$teamMember) {
                throw ValidationException::withMessages([
                    'players' => ["Player {$userId} is not an active member of the selected club squad."],
                ]);
            }

            $availability = $availabilityByUser->get($userId);

            if ($availability?->status === 'unavailable' && !$allowUnavailable) {
                throw ValidationException::withMessages([
                    'players' => ["Player {$userId} is marked unavailable for this match."],
                ]);
            }

            if ($availability?->status === 'maybe' && !$allowMaybe) {
                throw ValidationException::withMessages([
                    'players' => ["Player {$userId} is marked as maybe for this match."],
                ]);
            }

            return [
                'fixture_id' => $fixture->id,
                'team_id' => $team->id,
                'user_id' => $teamMember->user_id,
                'position' => $player['position'] ?? 'playing_xi',
                'jersey_number' => $player['jersey_number'] ?? $teamMember->jersey_number,
                'is_captain' => (bool) ($player['is_captain'] ?? false),
                'is_wicket_keeper' => (bool) ($player['is_wicket_keeper'] ?? false),
                'added_by' => $addedById,
            ];
        })->all();

        DB::transaction(function () use ($fixture, $team, $players) {
            $fixtureUpdates = $fixture->clubPlaysHome()
                ? ['home_team_id' => $team->id]
                : ['away_team_id' => $team->id];

            $fixture->update($fixtureUpdates);

            Squad::query()
                ->where('fixture_id', $fixture->id)
                ->where('team_id', $team->id)
                ->delete();

            Squad::insert(array_map(function (array $player) {
                $now = now();

                return array_merge($player, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, $players));
        });

        $fixture->refresh()->load(['homeTeam', 'awayTeam', 'venue', 'scorer', 'squads.player']);

        $selectedWithAvailability = collect($players)->map(function (array $player) use ($availabilityByUser) {
            $availability = $availabilityByUser->get($player['user_id']);

            return array_merge($player, [
                'availability_status' => $availability?->status ?? 'pending',
            ]);
        })->values();

        return response()->json([
            'message' => 'Club squad saved successfully.',
            'data' => [
                'fixture' => $this->formatFixture($fixture),
                'selected_players' => $selectedWithAvailability,
            ],
        ]);
    }


    public function setFixtureOpponentPlayers(Request $request, int $clubId, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveFixture($request, $clubId, $fixtureId);

        if (!$fixture) {
            return response()->json([
                'message' => 'Fixture not found or access denied.'
            ], 404);
        }

        if (in_array($fixture->status, ['live', 'paused', 'completed'], true)) {
            return response()->json([
                'message' => 'Cannot change opponent players after the match is live or completed.'
            ], 422);
        }

        $validated = $request->validate([
            'opponent_name' => 'sometimes|nullable|string|max:255',

            'players' => 'required|array|min:1|max:25',

            'players.*.name' => 'required|string|max:255',

            'players.*.role' => [
                'required',
                Rule::in([
                    'batsman',
                    'bowler',
                    'all_rounder',
                    'wicket_keeper',
                ]),
            ],

            'players.*.is_captain' => 'sometimes|boolean',
            'players.*.is_wicket_keeper' => 'sometimes|boolean',
        ]);

        $players = collect($validated['players'])
            ->map(function ($player) {

                return [
                    'name' => trim($player['name']),
                    'role' => $player['role'],
                    'is_captain' => (bool)($player['is_captain'] ?? false),
                    'is_wicket_keeper' => (bool)($player['is_wicket_keeper'] ?? false),
                ];
            })
            ->values()
            ->all();

        if (empty($players)) {
            return response()->json([
                'message' => 'Opponent players cannot be empty.'
            ], 422);
        }

        $updates = [];

        if ($fixture->clubPlaysHome()) {

            $updates['away_opponent_name'] = array_key_exists('opponent_name', $validated)
                ? trim((string)($validated['opponent_name'] ?? '')) ?: $fixture->away_opponent_name
                : $fixture->away_opponent_name;

            $updates['away_opponent_players'] = $players;

        } else {

            $updates['home_opponent_name'] = array_key_exists('opponent_name', $validated)
                ? trim((string)($validated['opponent_name'] ?? '')) ?: $fixture->home_opponent_name
                : $fixture->home_opponent_name;

            $updates['home_opponent_players'] = $players;
        }

        $fixture->update($updates);

        $fixture->load([
            'homeTeam',
            'awayTeam',
            'venue',
            'scorer',
            'squads.player',
        ]);

        return response()->json([
            'message' => 'Opponent players saved successfully.',
            'data' => [
                'fixture' => $this->formatFixture($fixture),
            ],
        ]);
    }

    // public function setFixtureOpponentPlayers(Request $request, int $clubId, int $fixtureId): JsonResponse
    // {
    //     $fixture = $this->resolveFixture($request, $clubId, $fixtureId);

    //     if (!$fixture) {
    //         return response()->json(['message' => 'Fixture not found or access denied.'], 404);
    //     }

    //     if (in_array($fixture->status, ['live', 'paused', 'completed'], true)) {
    //         return response()->json(['message' => 'Cannot change opponent players after the match is live or completed.'], 422);
    //     }

    //     $validated = $request->validate([
    //         'opponent_name' => 'sometimes|nullable|string|max:255',
    //         'players' => 'required|array|min:1|max:25',
    //         'players.*' => 'required|string|max:255',
    //     ]);

    //     $players = collect($validated['players'])
    //         ->map(fn ($player) => trim((string) $player))
    //         ->filter()
    //         ->values()
    //         ->all();

    //     if (!$players) {
    //         return response()->json(['message' => 'Opponent players cannot be empty.'], 422);
    //     }

    //     $updates = [];

    //     if ($fixture->clubPlaysHome()) {
    //         $updates['away_opponent_name'] = array_key_exists('opponent_name', $validated)
    //             ? trim((string) ($validated['opponent_name'] ?? '')) ?: $fixture->away_opponent_name
    //             : $fixture->away_opponent_name;
    //         $updates['away_opponent_players'] = $players;
    //     } else {
    //         $updates['home_opponent_name'] = array_key_exists('opponent_name', $validated)
    //             ? trim((string) ($validated['opponent_name'] ?? '')) ?: $fixture->home_opponent_name
    //             : $fixture->home_opponent_name;
    //         $updates['home_opponent_players'] = $players;
    //     }

    //     $fixture->update($updates);
    //     $fixture->load(['homeTeam', 'awayTeam', 'venue', 'scorer', 'squads.player']);

    //     return response()->json([
    //         'message' => 'Opponent players saved successfully.',
    //         'data' => [
    //             'fixture' => $this->formatFixture($fixture),
    //         ],
    //     ]);
    // }

    public function setFixtureScorer(Request $request, int $clubId, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveFixture($request, $clubId, $fixtureId);

        if (!$fixture) {
            return response()->json(['message' => 'Fixture not found or access denied.'], 404);
        }

        if (in_array($fixture->status, ['live', 'paused', 'completed'], true)) {
            return response()->json(['message' => 'Cannot change scorer after the match is live or completed.'], 422);
        }

        $club = $this->resolveClub($request, $clubId);

        $validated = $request->validate([
            'scorer_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        $isClubMember = ClubMember::query()
            ->where('club_id', $club->id)
            ->where('user_id', (int) $validated['scorer_user_id'])
            ->where('status', 'active')
            ->exists();

        if (!$isClubMember) {
            return response()->json(['message' => 'Selected scorer must be an active member of this club.'], 422);
        }

        $fixture->update([
            'scorer_user_id' => (int) $validated['scorer_user_id'],
            'scorer_assigned_at' => now(),
        ]);

        $fixture->load(['homeTeam', 'awayTeam', 'venue', 'scorer', 'squads.player']);

        return response()->json([
            'message' => 'Fixture scorer assigned successfully.',
            'data' => [
                'fixture' => $this->formatFixture($fixture),
            ],
        ]);
    }

    public function importFixtures(Request $request, int $clubId): JsonResponse
    {
        // dd($request->all());
        $club = $this->resolveClub($request, $clubId);

        if (!$club) {
            return response()->json(['message' => 'Club not found or access denied.'], 404);
        }

        $validated = $request->validate([
            'fixture_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('fixture_file');
        $rows = $this->readCsvRows($file->getRealPath());

        $import = FixtureImport::create([
            'club_id' => $club->id,
            'imported_by' => auth('sanctum')->id(),
            'source_type' => 'csv',
            'file_path' => '',
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'processing',
            'total_extracted' => count($rows),
            'total_imported' => 0,
            'total_failed' => 0,
            'started_processing_at' => now(),
        ]);

        $importPath = $this->storeUpload($request, 'fixture_file', 'fixture_imports');
        $import->update(['file_path' => $importPath]);

        $results = $this->processFixtureImportRows($club, auth('sanctum')->id(), $rows);

        $import->update([
            'parsed_fixtures' => $results['parsed_fixtures'] ?? [],
            'errors' => $results['errors'],
            'total_imported' => $results['imported'],
            'total_failed' => $results['failed'],
            'status' => $results['failed'] > 0 ? 'partial' : 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Fixture import completed.',
            'data' => [
                'import_id' => $import->id,
                'total_extracted' => $import->total_extracted,
                'total_imported' => $import->total_imported,
                'total_failed' => $import->total_failed,
                'errors' => $import->errors,
            ],
        ], 201);
    }

    private function resolveClub(Request $request, int $clubId): ?Club
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->user_type !== 'club') {
            return null;
        }

        $club = $user->ownedClub()->first();

        return $club && $club->id === $clubId ? $club : null;
    }

    private function resolveFixture(Request $request, int $clubId, int $fixtureId): ?Fixture
    {
        $club = $this->resolveClub($request, $clubId);

        if (!$club) {
            return null;
        }

        return Fixture::query()
            ->forClub($club->id)
            ->where('id', $fixtureId)
            ->first();
    }

    private function validateFixtureCreatePayload(Request $request): array
    {
        return $request->validate([
            'opponent_name' => 'required|string|max:255',
            'club_plays_home' => 'sometimes|boolean',
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'match_type' => ['required', Rule::in(['t10', 't20', 'odi_50', 'odi_40', 'test', 'custom'])],
            'overs_per_innings' => 'nullable|integer|min:1|max:500',
            'ball_type' => ['required', Rule::in(['leather', 'tennis', 'tape'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'live', 'paused', 'completed', 'abandoned', 'cancelled', 'postponed'])],
            'is_public' => 'sometimes|boolean',
        ]);
    }

    private function buildFixtureFromCreatePayload(array $validated): array
    {
        $clubPlaysHome = $validated['club_plays_home'] ?? true;
        $opponentName = trim($validated['opponent_name']);

        $payload = [
            'club_plays_home' => $clubPlaysHome,
            'venue_id' => $validated['venue_id'] ?? null,
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'match_type' => $validated['match_type'],
            'overs_per_innings' => $validated['overs_per_innings'] ?? null,
            'ball_type' => $validated['ball_type'],
            'status' => $validated['status'] ?? 'draft',
            'is_public' => $validated['is_public'] ?? true,
            'home_team_id' => null,
            'away_team_id' => null,
            'home_opponent_name' => null,
            'away_opponent_name' => null,
            'home_opponent_players' => null,
            'away_opponent_players' => null,
        ];

        if ($clubPlaysHome) {
            $payload['away_opponent_name'] = $opponentName;
        } else {
            $payload['home_opponent_name'] = $opponentName;
        }

        return $payload;
    }

    private function validateFixtureSchedulePayload(Request $request, Fixture $fixture): array
    {
        $validated = $request->validate([
            'opponent_name' => 'sometimes|string|max:255',
            'club_plays_home' => 'sometimes|boolean',
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'scheduled_date' => 'sometimes|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'match_type' => ['sometimes', Rule::in(['t10', 't20', 'odi_50', 'odi_40', 'test', 'custom'])],
            'overs_per_innings' => 'nullable|integer|min:1|max:500',
            'ball_type' => ['sometimes', Rule::in(['leather', 'tennis', 'tape'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'live', 'paused', 'completed', 'abandoned', 'cancelled', 'postponed'])],
            'is_public' => 'sometimes|boolean',
        ]);

        if (array_key_exists('opponent_name', $validated)) {
            $opponentName = trim($validated['opponent_name']);
            $clubPlaysHome = $validated['club_plays_home'] ?? $fixture->clubPlaysHome();

            if ($clubPlaysHome) {
                $validated['away_opponent_name'] = $opponentName;
                $validated['home_opponent_name'] = null;
            } else {
                $validated['home_opponent_name'] = $opponentName;
                $validated['away_opponent_name'] = null;
            }

            unset($validated['opponent_name']);
        }

        if (array_key_exists('club_plays_home', $validated)) {
            $validated['club_plays_home'] = (bool) $validated['club_plays_home'];
        }

        return $validated;
    }

    private function assertFixtureScorerRule(array $validated, Club $club): void
    {
        if (!array_key_exists('scorer_user_id', $validated) || $validated['scorer_user_id'] === null) {
            return;
        }

        $scorerUserId = (int) $validated['scorer_user_id'];
        $isClubMember = ClubMember::query()
            ->where('club_id', $club->id)
            ->where('user_id', $scorerUserId)
            ->where('status', 'active')
            ->exists();

        if (!$isClubMember) {
            throw ValidationException::withMessages([
                'scorer_user_id' => ['Selected scorer must be an active member of this club.'],
            ]);
        }
    }

    private function formatFixtureSide(?Team $team, ?string $opponentName, ?array $opponentPlayers = null): ?array
    {
        if ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'is_external' => false,
                'club_id' => $team->club_id,
                'opponent_players' => null,
            ];
        }

        if (filled($opponentName)) {
            return [
                'id' => null,
                'name' => $opponentName,
                'short_name' => null,
                'is_external' => true,
                'club_id' => null,
                'opponent_players' => $opponentPlayers ? array_values($opponentPlayers) : [],
            ];
        }

        return null;
    }

    private function formatFixtureSquad(Fixture $fixture): array
    {
        $teamId = $fixture->clubTeamId();

        if (!$teamId) {
            return [];
        }

        return $fixture->squads
            ->where('team_id', $teamId)
            ->map(function (Squad $squad) {
                $player = $squad->player;
                $fullName = trim(($player?->first_name ?? '') . ' ' . ($player?->last_name ?? ''));

                return [
                    'id' => $squad->id,
                    'user_id' => $squad->user_id,
                    'player_name' => $fullName ?: $player?->email,
                    'position' => $squad->position,
                    'position_label' => $squad->position_label,
                    'jersey_number' => $squad->jersey_number,
                    'is_captain' => (bool) $squad->is_captain,
                    'is_wicket_keeper' => (bool) $squad->is_wicket_keeper,
                ];
            })
            ->values()
            ->all();
    }

    private function formatFixture(Fixture $fixture): array
    {
        return [
            'id' => $fixture->id,
            'club_id' => $fixture->club_id,
            'home_team' => $this->formatFixtureSide($fixture->homeTeam, $fixture->home_opponent_name, $fixture->home_opponent_players),
            'away_team' => $this->formatFixtureSide($fixture->awayTeam, $fixture->away_opponent_name, $fixture->away_opponent_players),
            'home_display_name' => $fixture->home_display_name,
            'away_display_name' => $fixture->away_display_name,
            'scorer' => $fixture->scorer ? [
                'id' => $fixture->scorer->id,
                'first_name' => $fixture->scorer->first_name,
                'last_name' => $fixture->scorer->last_name,
                'email' => $fixture->scorer->email,
            ] : null,
            'scorer_user_id' => $fixture->scorer_user_id,
            'scorer_assigned_at' => $fixture->scorer_assigned_at?->toIso8601String(),
            'club_squad' => $this->formatFixtureSquad($fixture),
            'opponent_players' => $fixture->opponentPlayers() ? array_values($fixture->opponentPlayers()) : [],
            'is_match_ready' => $fixture->isMatchReady(),
            'venue' => $fixture->venue ? [
                'id' => $fixture->venue->id,
                'name' => $fixture->venue->name,
                'city' => $fixture->venue->city,
                'full_address' => $fixture->venue->full_address,
            ] : null,
            'scheduled_date' => $fixture->scheduled_date?->toDateString(),
            'scheduled_time' => $fixture->scheduled_time?->format('H:i'),
            'match_type' => $fixture->match_type,
            'match_type_label' => $fixture->match_type_label,
            'overs_per_innings' => $fixture->overs_per_innings,
            'ball_type' => $fixture->ball_type,
            'status' => $fixture->status,
            'status_label' => $fixture->status_label,
            'is_public' => (bool) $fixture->is_public,
            'public_share_slug' => $fixture->public_share_slug,
            'public_url' => $fixture->public_url,
            'is_live' => $fixture->isLive(),
            'has_match' => $fixture->relationLoaded('match') ? (bool) $fixture->match : null,
            'published_at' => $fixture->published_at?->toIso8601String(),
            'started_at' => $fixture->started_at?->toIso8601String(),
            'completed_at' => $fixture->completed_at?->toIso8601String(),
            'created_at' => $fixture->created_at?->toIso8601String(),
            'updated_at' => $fixture->updated_at?->toIso8601String(),
        ];
    }

    private function readCsvRows(string $path): array
    {
        $rows = [];

        if (!file_exists($path) || ($handle = fopen($path, 'r')) === false) {
            return $rows;
        }

        $header = null;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($column) => trim(strtolower($column)), $row);
                continue;
            }

            if (count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
                continue;
            }

            $rows[] = array_combine($header, array_map(fn ($value) => trim($value), $row));
        }

        fclose($handle);

        return $rows;
    }

    private function processFixtureImportRows(Club $club, int $userId, array $rows): array
    {
        $imported = 0;
        $failed = 0;
        $parsedFixtures = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $result = $this->buildFixtureFromRow($club, $row);

            if (!$result['valid']) {
                $failed++;
                $errors[] = ['row' => $rowNumber, 'errors' => $result['errors']];
                continue;
            }

            $fixture = Fixture::create(array_merge($result['data'], [
                'club_id' => $club->id,
                'created_by' => $userId,
                'status' => $result['data']['status'] ?? 'draft',
                'is_public' => $result['data']['is_public'] ?? true,
            ]));

            $imported++;
            $parsedFixtures[] = ['row' => $rowNumber, 'fixture_id' => $fixture->id];
        }

        return compact('imported', 'failed', 'parsedFixtures', 'errors');
    }

    private function buildFixtureFromRow(Club $club, array $row): array
    {
        $errors = [];
        $homeOpponentPlayers = $this->normalizePlayerNames($row['home_opponent_players'] ?? null);
        $awayOpponentPlayers = $this->normalizePlayerNames($row['away_opponent_players'] ?? null);
        $genericOpponentPlayers = $this->normalizePlayerNames($row['opponent_players'] ?? null);

        if (!empty($row['club_team_id'])) {
            $clubTeam = Team::where('id', (int) $row['club_team_id'])->where('club_id', $club->id)->first();

            if (!$clubTeam) {
                $errors[] = 'Invalid club_team_id';
            }

            $isHome = $this->normalizeBoolean($row['is_home'] ?? '1');
            $opponentTeamId = !empty($row['opponent_team_id']) && is_numeric($row['opponent_team_id'])
                ? (int) $row['opponent_team_id']
                : null;
            $opponentName = trim((string) ($row['opponent_name'] ?? ''));

            if (!$opponentTeamId && $opponentName === '') {
                $errors[] = 'Opponent is required (opponent_team_id or opponent_name)';
            }

            if ($opponentTeamId && $opponentName !== '') {
                $errors[] = 'Use either opponent_team_id or opponent_name, not both';
            }

            $homeTeam = $isHome ? $clubTeam : ($opponentTeamId ? Team::find($opponentTeamId) : null);
            $awayTeam = $isHome ? ($opponentTeamId ? Team::find($opponentTeamId) : null) : $clubTeam;
            $homeOpponentName = $isHome ? null : ($opponentName ?: null);
            $awayOpponentName = $isHome ? ($opponentName ?: null) : null;
            $homeOpponentPlayers = $isHome ? null : ($genericOpponentPlayers ?: $homeOpponentPlayers);
            $awayOpponentPlayers = $isHome ? ($genericOpponentPlayers ?: $awayOpponentPlayers) : null;
        } else {
            $homeTeam = $this->resolveTeamFromRow($club, $row, 'home', allowExternal: true);
            $awayTeam = $this->resolveTeamFromRow($club, $row, 'away', allowExternal: true);
            $homeOpponentName = trim((string) ($row['home_opponent_name'] ?? '')) ?: null;
            $awayOpponentName = trim((string) ($row['away_opponent_name'] ?? '')) ?: null;
            $homeOpponentPlayers = $homeOpponentName ? ($homeOpponentPlayers ?: null) : null;
            $awayOpponentPlayers = $awayOpponentName ? ($awayOpponentPlayers ?: $genericOpponentPlayers ?: null) : null;

            $hasHome = (bool) $homeTeam || $homeOpponentName;
            $hasAway = (bool) $awayTeam || $awayOpponentName;

            if (!$hasHome) {
                $errors[] = 'Invalid home team (home_team_id, home_team_slug, or home_opponent_name required)';
            }

            if (!$hasAway) {
                $errors[] = 'Invalid away team (away_team_id, away_team_slug, or away_opponent_name required)';
            }

            if ($homeTeam && $homeOpponentName) {
                $errors[] = 'Use either home team id/slug or home_opponent_name, not both';
            }

            if ($awayTeam && $awayOpponentName) {
                $errors[] = 'Use either away team id/slug or away_opponent_name, not both';
            }
        }

        if ($homeTeam && $awayTeam && $homeTeam->id === $awayTeam->id) {
            $errors[] = 'Home and away team must be different';
        }

        $clubTeamIds = Team::query()->where('club_id', $club->id)->pluck('id');
        $homeIsClub = $homeTeam && $clubTeamIds->contains($homeTeam->id);
        $awayIsClub = $awayTeam && $clubTeamIds->contains($awayTeam->id);

        if (!$homeIsClub && !$awayIsClub) {
            $errors[] = 'One side must be a squad from your club';
        }

        $scheduledDate = $row['scheduled_date'] ?? null;
        $scheduledTime = $row['scheduled_time'] ?? null;
        $matchType = $row['match_type'] ?? 't20';
        $oversPerInnings = $row['overs_per_innings'] ?? 20;
        $ballType = $row['ball_type'] ?? 'leather';
        $status = $row['status'] ?? 'draft';
        $isPublic = $this->normalizeBoolean($row['is_public'] ?? '1');

        if (!$scheduledDate || !strtotime($scheduledDate)) {
            $errors[] = 'Invalid scheduled_date';
        }

        if ($scheduledTime && !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $scheduledTime)) {
            $errors[] = 'Invalid scheduled_time';
        }

        if (!in_array($matchType, ['t10', 't20', 'odi_50', 'odi_40', 'test', 'custom'], true)) {
            $errors[] = 'Invalid match_type';
        }

        if (!is_numeric($oversPerInnings) || $oversPerInnings < 1) {
            $errors[] = 'Invalid overs_per_innings';
        }

        if (!in_array($ballType, ['leather', 'tennis', 'tape'], true)) {
            $errors[] = 'Invalid ball_type';
        }

        if (!in_array($status, ['draft', 'published', 'live', 'paused', 'completed', 'abandoned', 'cancelled', 'postponed'], true)) {
            $errors[] = 'Invalid status';
        }

        if ($errors) {
            return ['valid' => false, 'errors' => $errors];
        }

        $venueId = isset($row['venue_id']) && is_numeric($row['venue_id']) ? (int) $row['venue_id'] : null;

        return [
            'valid' => true,
            'data' => [
                'home_team_id' => $homeTeam?->id,
                'home_opponent_name' => $homeOpponentName ?? null,
                'home_opponent_players' => $homeOpponentPlayers ?? null,
                'away_team_id' => $awayTeam?->id,
                'away_opponent_name' => $awayOpponentName ?? null,
                'away_opponent_players' => $awayOpponentPlayers ?? null,
                'venue_id' => $venueId,
                'scheduled_date' => $scheduledDate,
                'scheduled_time' => $scheduledTime,
                'match_type' => $matchType,
                'overs_per_innings' => (int) $oversPerInnings,
                'ball_type' => $ballType,
                'status' => $status,
                'is_public' => $isPublic,
                'scorer_user_id' => isset($row['scorer_user_id']) && is_numeric($row['scorer_user_id']) ? (int) $row['scorer_user_id'] : null,
                'scorer_assigned_at' => isset($row['scorer_user_id']) && is_numeric($row['scorer_user_id']) ? now() : null,
            ],
        ];
    }

    private function normalizePlayerNames(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = preg_split('/\r\n|\r|\n|,/', $value) ?: [$value];
            }
        }

        if (!is_array($value)) {
            return null;
        }

        $players = [];

        foreach ($value as $player) {
            if (is_array($player)) {
                $player = $player['name'] ?? $player['full_name'] ?? '';
            }

            $name = trim((string) $player);

            if ($name !== '') {
                $players[] = $name;
            }
        }

        return $players ?: null;
    }

    private function resolveTeamFromRow(Club $club, array $row, string $prefix, bool $allowExternal = false): ?Team
    {
        $idKey = "{$prefix}_team_id";
        $slugKey = "{$prefix}_team_slug";

        if (!empty($row[$idKey]) && is_numeric($row[$idKey])) {
            $query = Team::where('id', (int) $row[$idKey]);

            if (!$allowExternal) {
                $query->where('club_id', $club->id);
            }

            return $query->first();
        }

        if (!empty($row[$slugKey])) {
            $query = Team::where('slug', $row[$slugKey]);

            if (!$allowExternal) {
                $query->where('club_id', $club->id);
            }

            return $query->first();
        }

        return null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function storeUpload(Request $request, string $field, string $folder): string
    {
        $file = $request->file($field);
        $storagePath = public_path('uploads/' . trim($folder, '/'));

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($storagePath, $fileName);

        return 'uploads/' . trim($folder, '/') . '/' . $fileName;
    }
}
