<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Matchs;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScorerController extends Controller
{
    public function __construct(private readonly ScoringService $scoring)
    {
    }

    public function readiness(Request $request, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveScorerFixture($request, $fixtureId);

        return response()->json([
            'message' => 'Match readiness fetched successfully.',
            'data' => $this->scoring->getReadiness($fixture),
        ]);
    }

    public function recordToss(Request $request, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveScorerFixture($request, $fixtureId);

        if ($fixture->match) {
            return response()->json(['message' => 'Cannot change toss after match has started.'], 422);
        }

        $validated = $request->validate([
            'winner_side' => ['required', Rule::in(['club', 'opponent'])],
            'decision' => ['required', Rule::in(['bat', 'bowl'])],
        ]);

        $fixture = $this->scoring->recordToss($fixture, $validated['winner_side'], $validated['decision']);

        return response()->json([
            'message' => 'Toss recorded successfully.',
            'data' => [
                'fixture_id' => $fixture->id,
                'toss_winner_side' => $fixture->toss_winner_side,
                'toss_decision' => $fixture->toss_decision,
                'readiness' => $this->scoring->getReadiness($fixture),
            ],
        ]);
    }

    public function startMatch(Request $request, int $fixtureId): JsonResponse
    {
        $fixture = $this->resolveScorerFixture($request, $fixtureId);
        $user = auth('sanctum')->user();

        $battingIsClub = $fixture->toss_winner_side === 'club'
            ? $fixture->toss_decision === 'bat'
            : $fixture->toss_decision === 'bowl';

        $rules = $battingIsClub
            ? [
                'striker_user_id' => 'required|integer|exists:users,id',
                'non_striker_user_id' => 'required|integer|exists:users,id|different:striker_user_id',
                'opening_bowler_player_index' => 'required|integer|min:0',
            ]
            : [
                'striker_player_index' => 'required|integer|min:0',
                'non_striker_player_index' => 'required|integer|min:0|different:striker_player_index',
                'opening_bowler_user_id' => 'required|integer|exists:users,id',
            ];

        $openers = $request->validate($rules);

        $match = $this->scoring->startMatch($fixture, $user, $openers);

        return response()->json([
            'message' => 'Match started successfully.',
            'data' => [
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'live' => $this->scoring->getLiveState($match),
            ],
        ], 201);
    }

    public function liveScore(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);

        return response()->json([
            'message' => 'Live score fetched successfully.',
            'data' => $this->scoring->getLiveState($match),
        ]);
    }

    public function recordBall(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);

        $validated = $request->validate([
            'event_type' => ['required', Rule::in(['dot', 'run', 'wide', 'no_ball', 'bye', 'leg_bye', 'wicket', 'penalty', 'retired', 'combo'])],
            'runs_scored' => 'sometimes|integer|min:0|max:6',
            'extras_runs' => 'sometimes|integer|min:0|max:10',
            'total_runs' => 'sometimes|integer|min:0|max:12',
            'extras_type' => ['nullable', Rule::in(['wide', 'no_ball', 'bye', 'leg_bye', 'penalty'])],
            'is_legal_delivery' => 'sometimes|boolean',
            'is_boundary_four' => 'sometimes|boolean',
            'is_boundary_six' => 'sometimes|boolean',
            'is_wicket_ball' => 'sometimes|boolean',
            'no_ball_type' => ['nullable', Rule::in(['overstepping', 'high_full_toss', 'above_waist', 'bounce_above_shoulders'])],
            'is_wide_plus_boundary' => 'sometimes|boolean',
            'commentary' => 'nullable|string|max:1000',
            'scorer_notes' => 'nullable|string|max:1000',
            'offline_uuid' => 'nullable|uuid',
            'wicket' => 'sometimes|array',
            'wicket.dismissal_type' => ['required_with:wicket', Rule::in([
                'bowled', 'caught', 'lbw', 'run_out', 'stumped', 'hit_wicket',
                'caught_and_bowled', 'retired', 'retired_hurt', 'obstructing_field',
                'hit_ball_twice', 'timed_out',
            ])],
            'wicket.fielder_one_user_id' => 'nullable|integer|exists:users,id',
            'wicket.fielder_one_player_index' => 'nullable|integer|min:0',
            'wicket.fielder_two_user_id' => 'nullable|integer|exists:users,id',
            'wicket.fielder_two_player_index' => 'nullable|integer|min:0',
            'wicket.description' => 'nullable|string|max:500',
        ]);

        if ($validated['event_type'] === 'dot') {
            $validated['runs_scored'] = 0;
            $validated['total_runs'] = 0;
        }

        if ($validated['event_type'] === 'wicket' && empty($validated['wicket'])) {
            $validated['wicket'] = ['dismissal_type' => 'bowled'];
        }

        $result = $this->scoring->recordBall($match, $validated);

        return response()->json([
            'message' => 'Ball recorded successfully.',
            'data' => [
                'ball' => $result['ball'],
                'wicket' => $result['wicket'],
                'live' => $this->scoring->getLiveState($result['match']),
            ],
        ], 201);
    }

    public function changeBowler(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'player_index' => 'nullable|integer|min:0',
        ]);

        if (empty($validated['user_id']) && !isset($validated['player_index'])) {
            return response()->json(['message' => 'Provide user_id or player_index for the new bowler.'], 422);
        }

        $innings = $this->scoring->changeBowler($match, $validated);

        return response()->json([
            'message' => 'Bowler changed successfully.',
            'data' => [
                'innings' => $innings,
                'live' => $this->scoring->getLiveState($match->fresh()),
            ],
        ]);
    }

    public function changeBatter(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);

        $validated = $request->validate([
            'side' => ['sometimes', Rule::in(['striker', 'non_striker'])],
            'user_id' => 'nullable|integer|exists:users,id',
            'player_index' => 'nullable|integer|min:0',
        ]);

        if (empty($validated['user_id']) && !isset($validated['player_index'])) {
            return response()->json(['message' => 'Provide user_id or player_index for the new batter.'], 422);
        }

        $innings = $this->scoring->changeBatter(
            $match,
            $validated,
            $validated['side'] ?? 'striker'
        );

        return response()->json([
            'message' => 'Batter changed successfully.',
            'data' => [
                'innings' => $innings,
                'live' => $this->scoring->getLiveState($match->fresh()),
            ],
        ]);
    }

    public function endInnings(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);

        $validated = $request->validate([
            'result' => ['sometimes', Rule::in(['all_out', 'overs_completed', 'target_achieved', 'innings_declared', 'abandoned'])],
            'result_note' => 'nullable|string|max:500',
        ]);

        $result = $this->scoring->endInnings(
            $match,
            $validated['result'] ?? 'overs_completed',
            $validated['result_note'] ?? null
        );

        return response()->json([
            'message' => 'Innings ended successfully.',
            'data' => $result,
        ]);
    }

    public function startSecondInnings(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);
        $fixture = $match->fixture;

        $battingIsClub = $match->firstInnings ? !$match->firstInnings->batting_is_club : true;

        $rules = $battingIsClub
            ? [
                'striker_user_id' => 'required|integer|exists:users,id',
                'non_striker_user_id' => 'required|integer|exists:users,id|different:striker_user_id',
                'opening_bowler_player_index' => 'required|integer|min:0',
            ]
            : [
                'striker_player_index' => 'required|integer|min:0',
                'non_striker_player_index' => 'required|integer|min:0|different:striker_player_index',
                'opening_bowler_user_id' => 'required|integer|exists:users,id',
            ];

        $openers = $request->validate($rules);
        $innings = $this->scoring->startSecondInnings($match, $openers);

        return response()->json([
            'message' => 'Second innings started successfully.',
            'data' => [
                'innings' => $innings,
                'live' => $this->scoring->getLiveState($match->fresh()),
            ],
        ]);
    }

    public function pauseMatch(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);
        $match->pause();
        $match->fixture->update(['status' => 'paused']);

        return response()->json([
            'message' => 'Match paused.',
            'data' => ['live' => $this->scoring->getLiveState($match->fresh())],
        ]);
    }

    public function resumeMatch(Request $request, int $matchId): JsonResponse
    {
        $match = $this->resolveScorerMatch($request, $matchId);
        $match->resume();
        $match->fixture->update(['status' => 'live']);

        return response()->json([
            'message' => 'Match resumed.',
            'data' => ['live' => $this->scoring->getLiveState($match->fresh())],
        ]);
    }

    private function resolveScorerFixture(Request $request, int $fixtureId): Fixture
    {
        $user = auth('sanctum')->user();
        $fixture = Fixture::with(['match', 'squads.player'])->find($fixtureId);

        if (!$fixture) {
            abort(response()->json(['message' => 'Fixture not found.'], 404));
        }

        if (!$this->userCanScoreFixture($user, $fixture)) {
            abort(response()->json(['message' => 'You are not authorized to score this match.'], 403));
        }

        return $fixture;
    }

    private function resolveScorerMatch(Request $request, int $matchId): Matchs
    {
        $user = auth('sanctum')->user();
        $match = Matchs::with(['fixture', 'firstInnings', 'secondInnings'])->find($matchId);

        if (!$match) {
            abort(response()->json(['message' => 'Match not found.'], 404));
        }

        if (!$this->userCanScoreFixture($user, $match->fixture)) {
            abort(response()->json(['message' => 'You are not authorized to score this match.'], 403));
        }

        return $match;
    }

    private function userCanScoreFixture(User $user, Fixture $fixture): bool
    {
        if ($fixture->scorer_user_id === $user->id) {
            return true;
        }

        if ($fixture->match && $user->isScorerForMatch($fixture->match)) {
            return true;
        }

        return false;
    }
}
