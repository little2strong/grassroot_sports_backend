<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Availability;
use App\Models\ClubMember;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    public function listFixtures(Request $request): JsonResponse
    {
        $user = $this->resolvePlayer($request);

        $validated = $request->validate([
            'club_id' => 'sometimes|integer',
            'status' => ['sometimes', Rule::in(['published', 'live', 'paused', 'completed', 'abandoned', 'cancelled', 'postponed'])],
            'upcoming' => 'sometimes|boolean',
            'my_squads_only' => 'sometimes|boolean',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after_or_equal:from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $clubIds = $this->playerClubIds($user);
        $teamIds = $this->playerTeamIds($user);

        if ($clubIds->isEmpty()) {
            return response()->json([
                'message' => 'No active club membership found.',
                'data' => ['fixtures' => []],
            ]);
        }

        if (!empty($validated['club_id']) && !$clubIds->contains((int) $validated['club_id'])) {
            return response()->json(['message' => 'Club not found or access denied.'], 404);
        }

        $query = Fixture::query()
            ->published()
            ->with([
                'club:id,name,slug,short_name,logo',
                'homeTeam:id,club_id,name,short_name',
                'awayTeam:id,club_id,name,short_name',
                'venue:id,name,city,address,country',
                'match',
                'availability' => fn ($q) => $q->where('user_id', $user->id),
                'squads' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->whereIn('club_id', $clubIds)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');

        if (!empty($validated['club_id'])) {
            $query->forClub((int) $validated['club_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if ($request->boolean('upcoming')) {
            $query->whereIn('status', ['published', 'live', 'paused'])
                ->where('scheduled_date', '>=', now()->toDateString());
        }

        if ($request->boolean('my_squads_only')) {
            $query->where(function ($builder) use ($teamIds) {
                $builder->whereIn('home_team_id', $teamIds)
                    ->orWhereIn('away_team_id', $teamIds);
            });
        }

        if (!empty($validated['from'])) {
            $query->where('scheduled_date', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->where('scheduled_date', '<=', $validated['to']);
        }

        $fixtures = $query->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'message' => 'Club fixtures fetched successfully.',
            'data' => [
                'fixtures' => $fixtures->through(fn (Fixture $fixture) => $this->formatPlayerFixture($fixture, $user, $teamIds)),
            ],
        ]);
    }

    public function showFixture(Request $request, int $fixtureId): JsonResponse
    {
        $user = $this->resolvePlayer($request);
        $clubIds = $this->playerClubIds($user);
        $teamIds = $this->playerTeamIds($user);

        $fixture = Fixture::query()
            ->published()
            ->with([
                'club:id,name,slug,short_name,logo',
                'homeTeam:id,club_id,name,short_name',
                'awayTeam:id,club_id,name,short_name',
                'venue:id,name,city,address,country',
                'match',
                'scorer:id,first_name,last_name,email',
                'availability' => fn ($q) => $q->where('user_id', $user->id),
                'squads' => fn ($q) => $q->where('user_id', $user->id)->with('team:id,name,short_name'),
            ])
            ->whereIn('club_id', $clubIds)
            ->where('id', $fixtureId)
            ->first();

        if (!$fixture) {
            return response()->json(['message' => 'Fixture not found or access denied.'], 404);
        }

        return response()->json([
            'message' => 'Fixture fetched successfully.',
            'data' => [
                'fixture' => $this->formatPlayerFixture($fixture, $user, $teamIds, detailed: true),
            ],
        ]);
    }

    public function setFixtureAvailability(Request $request, int $fixtureId): JsonResponse
    {
        $user = $this->resolvePlayer($request);

        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'status' => ['required', Rule::in(['available', 'maybe', 'unavailable'])],
            'reason' => 'nullable|string|max:1000|required_if:status,unavailable',
        ]);

        $fixture = $this->resolvePlayerFixture($user, $fixtureId);
        dd($fixture, $fixtureId);

        if (!$fixture) {
            return response()->json(['message' => 'Fixture not found or access denied.'], 404);
        }

        if ($this->fixtureAvailabilityLocked($fixture)) {
            return response()->json(['message' => 'Availability cannot be changed after the match has started or completed.'], 422);
        }

        $team = $this->resolvePlayerTeamForFixture($user, $fixture, (int) $validated['team_id']);

        if (!$team) {
            return response()->json(['message' => 'You are not an active member of this squad for this fixture.'], 422);
        }

        $availability = Availability::updateOrCreate(
            [
                'fixture_id' => $fixture->id,
                'user_id' => $user->id,
            ],
            [
                'team_id' => $team->id,
                'status' => $validated['status'],
                'reason' => $validated['reason'] ?? null,
                'responded_at' => now(),
            ]
        );

        $club = $fixture->club;

        AppNotification::create([
            'user_id' => $club->owner_id,
            'type' => AppNotification::TYPE_AVAILABILITY_REQUESTED,
            'title' => 'Availability update',
            'message' => $user->full_name . ' has responded ' . $validated['status'] . ' for ' . $fixture->home_display_name . ' vs ' . $fixture->away_display_name . '.',
            'notifiable_type' => Availability::class,
            'notifiable_id' => $availability->id,
            'data' => [
                'availability_id' => $availability->id,
                'fixture_id' => $fixture->id,
                'club_id' => $club->id,
                'club_name' => $club->name,
                'player_id' => $user->id,
                'player_name' => $user->full_name,
                'status' => $validated['status'],
            ],
        ]);

        return response()->json([
            'message' => 'Availability saved successfully.',
            'data' => [
                'availability' => $this->formatAvailability($availability),
            ],
        ]);
    }

    public function bulkSetAvailability(Request $request): JsonResponse
    {
        $user = $this->resolvePlayer($request);

        $validated = $request->validate([
            'responses' => 'required|array|min:1|max:50',
            'responses.*.fixture_id' => 'required|integer|distinct',
            'responses.*.team_id' => 'required|integer|exists:teams,id',
            'responses.*.status' => ['required', Rule::in(['available', 'maybe', 'unavailable'])],
            'responses.*.reason' => 'nullable|string|max:1000',
        ]);

        $saved = [];
        $errors = [];

        foreach ($validated['responses'] as $index => $response) {
            if ($response['status'] === 'unavailable' && empty(trim((string) ($response['reason'] ?? '')))) {
                $errors[] = [
                    'index' => $index,
                    'fixture_id' => $response['fixture_id'],
                    'message' => 'Reason is required when status is unavailable.',
                ];
                continue;
            }

            $fixture = $this->resolvePlayerFixture($user, (int) $response['fixture_id']);

            if (!$fixture) {
                $errors[] = [
                    'index' => $index,
                    'fixture_id' => $response['fixture_id'],
                    'message' => 'Fixture not found or access denied.',
                ];
                continue;
            }

            if ($this->fixtureAvailabilityLocked($fixture)) {
                $errors[] = [
                    'index' => $index,
                    'fixture_id' => $response['fixture_id'],
                    'message' => 'Availability cannot be changed after the match has started or completed.',
                ];
                continue;
            }

            $team = $this->resolvePlayerTeamForFixture($user, $fixture, (int) $response['team_id']);

            if (!$team) {
                $errors[] = [
                    'index' => $index,
                    'fixture_id' => $response['fixture_id'],
                    'message' => 'You are not an active member of this squad for this fixture.',
                ];
                continue;
            }

            $availability = Availability::updateOrCreate(
                [
                    'fixture_id' => $fixture->id,
                    'user_id' => $user->id,
                ],
                [
                    'team_id' => $team->id,
                    'status' => $response['status'],
                    'reason' => $response['reason'] ?? null,
                    'responded_at' => now(),
                ]
            );

            $saved[] = $this->formatAvailability($availability);
        }

        $statusCode = empty($saved) ? 422 : (empty($errors) ? 200 : 207);

        return response()->json([
            'message' => empty($errors)
                ? 'Availability saved successfully.'
                : 'Availability saved with some errors.',
            'data' => [
                'saved' => $saved,
                'errors' => $errors,
            ],
        ], $statusCode);
    }

    private function resolvePlayerFixture(User $user, int $fixtureId): ?Fixture
    {
        $clubIds = $this->playerClubIds($user);

        return Fixture::query()
            ->published()
            ->whereIn('club_id', $clubIds)
            ->where('id', $fixtureId)
            ->first();
    }

    private function resolvePlayerTeamForFixture(User $user, Fixture $fixture, int $teamId): ?Team
    {
        $team = Team::query()
            ->where('id', $teamId)
            ->where('club_id', $fixture->club_id)
            ->first();

        if (!$team) {
            return null;
        }

        $isMember = TeamMember::query()
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        return $isMember ? $team : null;
    }

    private function fixtureAvailabilityLocked(Fixture $fixture): bool
    {
        return in_array($fixture->status, ['live', 'paused', 'completed', 'abandoned', 'cancelled'], true);
    }

    private function formatAvailability(Availability $availability): array
    {
        return [
            'id' => $availability->id,
            'fixture_id' => $availability->fixture_id,
            'team_id' => $availability->team_id,
            'user_id' => $availability->user_id,
            'status' => $availability->status,
            'status_label' => $availability->status_label,
            'reason' => $availability->reason,
            'responded_at' => $availability->responded_at?->toIso8601String(),
            'updated_at' => $availability->updated_at?->toIso8601String(),
        ];
    }

    private function resolvePlayer(Request $request): User
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->user_type !== 'player') {
            abort(response()->json([
                'message' => 'Only player accounts can access player fixtures.',
            ], 403));
        }

        return $user;
    }

    private function playerClubIds(User $user): Collection
    {
        return ClubMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('club_id');
    }

    private function playerTeamIds(User $user): Collection
    {
        return $user->teams()->pluck('teams.id');
    }

    private function formatPlayerFixture(Fixture $fixture, User $user, Collection $playerTeamIds, bool $detailed = false): array
    {
        $myTeam = $this->resolvePlayerTeamInFixture($fixture, $playerTeamIds);
        $mySquad = $fixture->squads->first();
        $myAvailability = $fixture->availability->first();

        $data = [
            'id' => $fixture->id,
            'club' => $fixture->club ? [
                'id' => $fixture->club->id,
                'name' => $fixture->club->name,
                'slug' => $fixture->club->slug,
                'short_name' => $fixture->club->short_name,
                'logo_url' => $this->assetUrl($fixture->club->logo),
            ] : null,
            'home_team' => $this->formatFixtureSide($fixture->homeTeam, $fixture->home_opponent_name),
            'away_team' => $this->formatFixtureSide($fixture->awayTeam, $fixture->away_opponent_name),
            'home_display_name' => $fixture->home_display_name,
            'away_display_name' => $fixture->away_display_name,
            'opponent_name' => $fixture->opponentName(),
            'my_team' => $myTeam,
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
            'is_live' => $fixture->isLive(),
            'public_url' => $fixture->public_url,
            'result_text' => $fixture->result_text,
            'home_score' => $fixture->isLive() || $fixture->isCompleted() ? [
                'runs' => $fixture->home_team_runs,
                'wickets' => $fixture->home_team_wickets,
                'overs' => $fixture->home_team_overs,
                'display' => $fixture->home_score_display,
            ] : null,
            'away_score' => $fixture->isLive() || $fixture->isCompleted() ? [
                'runs' => $fixture->away_team_runs,
                'wickets' => $fixture->away_team_wickets,
                'overs' => $fixture->away_team_overs,
                'display' => $fixture->away_score_display,
            ] : null,
            'my_selection' => $mySquad ? [
                'is_selected' => true,
                'position' => $mySquad->position,
                'position_label' => $mySquad->position_label,
                'jersey_number' => $mySquad->jersey_number,
                'is_captain' => (bool) $mySquad->is_captain,
                'is_wicket_keeper' => (bool) $mySquad->is_wicket_keeper,
            ] : [
                'is_selected' => false,
                'position' => null,
                'position_label' => null,
                'jersey_number' => null,
                'is_captain' => false,
                'is_wicket_keeper' => false,
            ],
            'my_availability' => $myAvailability ? [
                'status' => $myAvailability->status,
                'status_label' => $myAvailability->status_label,
                'reason' => $myAvailability->reason,
                'responded_at' => $myAvailability->responded_at?->toIso8601String(),
            ] : null,
            'published_at' => $fixture->published_at?->toIso8601String(),
            'started_at' => $fixture->started_at?->toIso8601String(),
            'completed_at' => $fixture->completed_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['scorer'] = $fixture->scorer ? [
                'id' => $fixture->scorer->id,
                'first_name' => $fixture->scorer->first_name,
                'last_name' => $fixture->scorer->last_name,
                'full_name' => trim($fixture->scorer->first_name . ' ' . $fixture->scorer->last_name),
            ] : null;
            $data['is_match_ready'] = $fixture->isMatchReady();
            $data['has_match'] = (bool) $fixture->match;
        }

        return $data;
    }

    private function resolvePlayerTeamInFixture(Fixture $fixture, Collection $playerTeamIds): ?array
    {
        if ($fixture->home_team_id && $playerTeamIds->contains($fixture->home_team_id)) {
            return [
                'id' => $fixture->homeTeam?->id,
                'name' => $fixture->homeTeam?->name,
                'short_name' => $fixture->homeTeam?->short_name,
                'side' => 'home',
            ];
        }

        if ($fixture->away_team_id && $playerTeamIds->contains($fixture->away_team_id)) {
            return [
                'id' => $fixture->awayTeam?->id,
                'name' => $fixture->awayTeam?->name,
                'short_name' => $fixture->awayTeam?->short_name,
                'side' => 'away',
            ];
        }

        $clubTeamId = $fixture->clubTeamId();

        if ($clubTeamId && $playerTeamIds->contains($clubTeamId)) {
            $team = $fixture->clubTeam();

            return [
                'id' => $team?->id,
                'name' => $team?->name,
                'short_name' => $team?->short_name,
                'side' => $fixture->clubPlaysHome() ? 'home' : 'away',
            ];
        }

        return null;
    }

    private function formatFixtureSide(?\App\Models\Team $team, ?string $opponentName): ?array
    {
        if ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'is_external' => false,
            ];
        }

        if (filled($opponentName)) {
            return [
                'id' => null,
                'name' => $opponentName,
                'short_name' => null,
                'is_external' => true,
            ];
        }

        return null;
    }

    private function assetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }
}
