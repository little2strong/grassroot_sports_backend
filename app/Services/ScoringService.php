<?php

namespace App\Services;

use App\Models\BallEvent;
use App\Models\BattingScore;
use App\Models\BowlingFigure;
use App\Models\Fixture;
use App\Models\Innings;
use App\Models\MatchScorer;
use App\Models\Matchs;
use App\Models\OverSummary;
use App\Models\Squad;
use App\Models\User;
use App\Models\Wicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScoringService
{
    public function getReadiness(Fixture $fixture): array
    {
        $clubTeamId = $fixture->clubTeamId();
        $clubSquad = $clubTeamId
            ? $fixture->squads()->where('team_id', $clubTeamId)->where('position', 'playing_xi')->with('player')->get()
            : collect();
        $opponentPlayers = $fixture->opponentPlayers() ?? [];

        $checks = [
            'scorer_assigned' => $fixture->scorer_user_id !== null,
            'club_team_assigned' => $clubTeamId !== null,
            'club_squad_assigned' => $clubSquad->isNotEmpty(),
            'opponent_squad_assigned' => count($opponentPlayers) > 0,
            'toss_recorded' => filled($fixture->toss_winner_side) && filled($fixture->toss_decision),
            'match_not_started' => !$fixture->match,
        ];

        $canStart = collect($checks)->except('match_not_started')->every(fn ($v) => $v === true)
            && $checks['match_not_started'];

        return [
            'checks' => $checks,
            'can_start_match' => $canStart,
            'is_match_ready' => $fixture->isMatchReady(),
            'club_team_id' => $clubTeamId,
            'club_squad' => $clubSquad->map(fn (Squad $s) => $this->formatClubSquadPlayer($s))->values(),
            'opponent_players' => array_values($opponentPlayers),
            'toss' => [
                'winner_side' => $fixture->toss_winner_side,
                'decision' => $fixture->toss_decision,
            ],
            'scorer_user_id' => $fixture->scorer_user_id,
            'missing' => collect([
                'scorer_assigned' => 'Assign a scorer for this fixture.',
                'club_team_assigned' => 'Assign a club squad to this fixture.',
                'club_squad_assigned' => 'Select playing XI for the club squad.',
                'opponent_squad_assigned' => 'Add opponent players before starting the match.',
                'toss_recorded' => 'Record toss before starting the match.',
            ])->filter(fn ($msg, $key) => isset($checks[$key]) && !$checks[$key])->values(),
        ];
    }

    public function recordToss(Fixture $fixture, string $winnerSide, string $decision): Fixture
    {
        if (!in_array($winnerSide, ['club', 'opponent'], true)) {
            throw ValidationException::withMessages(['winner_side' => ['Invalid toss winner side.']]);
        }

        if (!in_array($decision, ['bat', 'bowl'], true)) {
            throw ValidationException::withMessages(['decision' => ['Invalid toss decision.']]);
        }

        $fixture->update([
            'toss_winner_side' => $winnerSide,
            'toss_decision' => $decision,
            'toss_winner_team_id' => $winnerSide === 'club' ? $fixture->clubTeamId() : null,
        ]);

        return $fixture->fresh();
    }

    public function startMatch(Fixture $fixture, User $scorer, array $openers): Matchs
    {
        $readiness = $this->getReadiness($fixture);

        if (!$readiness['can_start_match']) {
            throw ValidationException::withMessages([
                'fixture' => ['Fixture is not ready to start. Complete all pre-match steps first.'],
            ]);
        }

        if (!filled($fixture->toss_winner_side) || !filled($fixture->toss_decision)) {
            throw ValidationException::withMessages(['toss' => ['Record toss before starting the match.']]);
        }

        $battingIsClub = $this->resolveBattingIsClub($fixture);
        $clubTeamId = $fixture->clubTeamId();

        return DB::transaction(function () use ($fixture, $scorer, $openers, $battingIsClub, $clubTeamId) {
            $match = Matchs::create([
                'fixture_id' => $fixture->id,
                'current_innings_number' => 1,
                'current_over_number' => 0,
                'current_ball_number' => 0,
                'total_legal_deliveries' => 0,
                'is_paused' => false,
                'current_powerplay' => 'none',
            ]);

            MatchScorer::create([
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'user_id' => $scorer->id,
                'role' => 'primary_scorer',
                'assigned_by' => $scorer->id,
                'assigned_at' => now(),
            ]);

            $innings = $this->createInnings($match, $fixture, 1, $battingIsClub, $clubTeamId);
            $this->initializeBattingScores($innings, $fixture, $battingIsClub, $clubTeamId);
            $this->setOpeners($innings, $fixture, $openers, $battingIsClub, $clubTeamId);
            $this->setOpeningBowler($innings, $fixture, $openers, $battingIsClub, $clubTeamId);

            $match->update(['first_innings_id' => $innings->id]);
            $fixture->update([
                'status' => 'live',
                'started_at' => now(),
            ]);

            return $match->fresh(['fixture', 'firstInnings.battingScores', 'firstInnings.bowlingFigures']);
        });
    }

    public function recordBall(Matchs $match, array $payload): array
    {
        if ($match->is_paused) {
            throw ValidationException::withMessages(['match' => ['Match is paused. Resume before scoring.']]);
        }

        $innings = $match->currentInnings();

        if (!$innings || !$innings->isInProgress()) {
            throw ValidationException::withMessages(['innings' => ['No active innings to score.']]);
        }

        return DB::transaction(function () use ($match, $innings, $payload) {
            $fixture = $match->fixture;
            $eventType = $payload['event_type'];
            $runsScored = (int) ($payload['runs_scored'] ?? 0);
            $extrasRuns = (int) ($payload['extras_runs'] ?? 0);
            $isLegal = (bool) ($payload['is_legal_delivery'] ?? !in_array($eventType, ['wide', 'no_ball'], true));
            $totalRuns = (int) ($payload['total_runs'] ?? ($runsScored + $extrasRuns));
            $isWicket = (bool) ($payload['is_wicket_ball'] ?? ($eventType === 'wicket'));
            $isFour = (bool) ($payload['is_boundary_four'] ?? false);
            $isSix = (bool) ($payload['is_boundary_six'] ?? false);

            $ballSequence = ($innings->ballEvents()->max('ball_sequence') ?? 0) + 1;
            $legalBallSequence = $isLegal
                ? ($innings->ballEvents()->where('is_legal_delivery', true)->max('legal_ball_sequence') ?? 0) + 1
                : ($innings->ballEvents()->where('is_legal_delivery', true)->max('legal_ball_sequence') ?? 0);

            $overNumber = $match->current_over_number;
            $ballNumber = $isLegal ? $match->current_ball_number + 1 : max($match->current_ball_number, 1);

            $strikerRef = $this->currentStrikerRef($innings);
            $nonStrikerRef = $this->currentNonStrikerRef($innings);
            $bowlerRef = $this->currentBowlerRef($innings);

            $ball = BallEvent::create([
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'over_number' => $overNumber,
                'ball_number' => $ballNumber,
                'ball_sequence' => $ballSequence,
                'legal_ball_sequence' => $legalBallSequence,
                'striker_id' => $strikerRef['user_id'],
                'non_striker_id' => $nonStrikerRef['user_id'],
                'bowler_id' => $bowlerRef['user_id'],
                'external_striker_index' => $strikerRef['external_index'],
                'external_non_striker_index' => $nonStrikerRef['external_index'],
                'external_bowler_index' => $bowlerRef['external_index'],
                'batting_team_id' => $innings->batting_team_id,
                'bowling_team_id' => $innings->bowling_team_id,
                'event_type' => $eventType,
                'runs_scored' => $runsScored,
                'total_runs' => $totalRuns,
                'is_boundary_four' => $isFour,
                'is_boundary_six' => $isSix,
                'extras_type' => $payload['extras_type'] ?? null,
                'extras_runs' => $extrasRuns,
                'is_legal_delivery' => $isLegal,
                'is_wicket_ball' => $isWicket,
                'no_ball_type' => $payload['no_ball_type'] ?? null,
                'is_wide_plus_boundary' => (bool) ($payload['is_wide_plus_boundary'] ?? false),
                'commentary' => $payload['commentary'] ?? null,
                'scorer_notes' => $payload['scorer_notes'] ?? null,
                'offline_uuid' => $payload['offline_uuid'] ?? null,
            ]);

            $wicket = null;

            if ($isWicket) {
                $wicket = $this->recordWicket($ball, $innings, $match, $fixture, $payload['wicket'] ?? []);
                $ball->update(['wicket_id' => $wicket->id]);
            }

            $this->applyBallToInnings($innings, $eventType, $totalRuns, $extrasRuns, $isLegal, $isWicket);
            $this->applyBallToBattingScore($innings, $strikerRef, $runsScored, $isLegal, $isFour, $isSix, $isWicket);
            $this->applyBallToBowlingFigure($innings, $bowlerRef, $totalRuns, $eventType, $isWicket, $wicket);
            $this->advanceMatchCursor($match, $innings, $isLegal, $runsScored, $isWicket);
            $this->syncFixtureScore($fixture, $innings);
            $this->checkInningsEnd($innings, $match, $fixture);

            return [
                'ball' => $ball->fresh(['wicket']),
                'innings' => $innings->fresh(),
                'match' => $match->fresh(),
                'wicket' => $wicket,
            ];
        });
    }

    public function changeBowler(Matchs $match, array $bowler): Innings
    {
        $innings = $match->currentInnings();

        if (!$innings || !$innings->isInProgress()) {
            throw ValidationException::withMessages(['innings' => ['No active innings.']]);
        }

        $fixture = $match->fixture;
        $ref = $this->resolvePlayerRef($bowler, $fixture, !$innings->batting_is_club);

        if ($innings->bowling_is_club && !$ref['user_id']) {
            throw ValidationException::withMessages(['bowler' => ['Club bowler requires user_id.']]);
        }

        if (!$innings->bowling_is_club && $ref['external_index'] === null) {
            throw ValidationException::withMessages(['bowler' => ['Opponent bowler requires player_index.']]);
        }

        BowlingFigure::query()
            ->where('innings_id', $innings->id)
            ->update(['is_current_bowler' => false]);

        $figure = BowlingFigure::firstOrCreate(
            [
                'innings_id' => $innings->id,
                'user_id' => $ref['user_id'],
                'external_player_index' => $ref['external_index'],
            ],
            [
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'team_id' => $innings->bowling_team_id,
                'external_player_name' => $ref['name'],
                'is_current_bowler' => true,
            ]
        );

        $figure->update(['is_current_bowler' => true]);

        $innings->update([
            'current_bowler_id' => $ref['user_id'],
            'external_bowler_index' => $ref['external_index'],
        ]);

        return $innings->fresh();
    }

    public function changeBatter(Matchs $match, array $batter, string $side = 'striker'): Innings
    {
        $innings = $match->currentInnings();

        if (!$innings || !$innings->isInProgress()) {
            throw ValidationException::withMessages(['innings' => ['No active innings.']]);
        }

        $fixture = $match->fixture;
        $ref = $this->resolvePlayerRef($batter, $fixture, $innings->batting_is_club);

        if ($side === 'striker') {
            $innings->update([
                'striker_id' => $ref['user_id'],
                'external_striker_index' => $ref['external_index'],
            ]);
            $this->markBatterOnStrike($innings, $ref, true);
        } else {
            $innings->update([
                'non_striker_id' => $ref['user_id'],
                'external_non_striker_index' => $ref['external_index'],
            ]);
            $this->markBatterOnStrike($innings, $ref, false);
        }

        return $innings->fresh();
    }

    public function endInnings(Matchs $match, string $result, ?string $note = null): array
    {
        $innings = $match->currentInnings();

        if (!$innings || !$innings->isInProgress()) {
            throw ValidationException::withMessages(['innings' => ['No active innings to end.']]);
        }

        $innings->update([
            'result' => $result,
            'result_note' => $note,
            'ended_at' => now(),
        ]);

        $fixture = $match->fixture;

        if ($match->current_innings_number === 1) {
            return [
                'innings' => $innings->fresh(),
                'match' => $match->fresh(),
                'next_step' => 'start_second_innings',
                'target' => $innings->runs + 1,
            ];
        }

        $fixture->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return [
            'innings' => $innings->fresh(),
            'match' => $match->fresh(),
            'next_step' => 'complete_match',
        ];
    }

    public function startSecondInnings(Matchs $match, array $openers): Innings
    {
        if ($match->current_innings_number !== 1 || !$match->firstInnings || $match->firstInnings->isInProgress()) {
            throw ValidationException::withMessages(['innings' => ['First innings must be completed before starting second innings.']]);
        }

        $fixture = $match->fixture;
        $battingIsClub = !$match->firstInnings->batting_is_club;
        $clubTeamId = $fixture->clubTeamId();
        $target = $match->firstInnings->runs + 1;

        return DB::transaction(function () use ($match, $fixture, $openers, $battingIsClub, $clubTeamId, $target) {
            $innings = $this->createInnings($match, $fixture, 2, $battingIsClub, $clubTeamId, $target);
            $this->initializeBattingScores($innings, $fixture, $battingIsClub, $clubTeamId);
            $this->setOpeners($innings, $fixture, $openers, $battingIsClub, $clubTeamId);
            $this->setOpeningBowler($innings, $fixture, $openers, $battingIsClub, $clubTeamId);

            $match->update([
                'second_innings_id' => $innings->id,
                'current_innings_number' => 2,
                'current_over_number' => 0,
                'current_ball_number' => 0,
                'total_legal_deliveries' => 0,
            ]);

            return $innings->fresh(['battingScores', 'bowlingFigures']);
        });
    }

    public function getLiveState(Matchs $match): array
    {
        $fixture = $match->fixture;
        $innings = $match->currentInnings();

        return [
            'match' => [
                'id' => $match->id,
                'fixture_id' => $match->fixture_id,
                'current_innings_number' => $match->current_innings_number,
                'current_over_display' => $match->current_over_display,
                'is_paused' => $match->is_paused,
            ],
            'fixture' => [
                'id' => $fixture->id,
                'status' => $fixture->status,
                'home_display_name' => $fixture->home_display_name,
                'away_display_name' => $fixture->away_display_name,
                'overs_per_innings' => $fixture->overs_per_innings,
                'toss_winner_side' => $fixture->toss_winner_side,
                'toss_decision' => $fixture->toss_decision,
            ],
            'innings' => $innings ? $this->formatInningsLive($innings) : null,
            'recent_balls' => $innings
                ? $innings->ballEvents()->with('wicket')->latest('ball_sequence')->limit(12)->get()->reverse()->values()
                : [],
            'first_innings' => $match->firstInnings ? $this->formatInningsLive($match->firstInnings) : null,
            'second_innings' => $match->secondInnings ? $this->formatInningsLive($match->secondInnings) : null,
        ];
    }

    private function resolveBattingIsClub(Fixture $fixture): bool
    {
        $clubWonToss = $fixture->toss_winner_side === 'club';
        $choseBat = $fixture->toss_decision === 'bat';

        return ($clubWonToss && $choseBat) || (!$clubWonToss && !$choseBat);
    }

    private function createInnings(
        Matchs $match,
        Fixture $fixture,
        int $inningsNumber,
        bool $battingIsClub,
        ?int $clubTeamId,
        ?int $target = null
    ): Innings {
        return Innings::create([
            'match_id' => $match->id,
            'fixture_id' => $fixture->id,
            'innings_number' => $inningsNumber,
            'batting_team_id' => $battingIsClub ? $clubTeamId : null,
            'bowling_team_id' => $battingIsClub ? null : $clubTeamId,
            'batting_is_club' => $battingIsClub,
            'bowling_is_club' => !$battingIsClub,
            'target' => $target,
            'result' => 'in_progress',
            'total_batters' => 11,
            'started_at' => now(),
        ]);
    }

    private function initializeBattingScores(Innings $innings, Fixture $fixture, bool $battingIsClub, ?int $clubTeamId): void
    {
        if ($battingIsClub) {
            $squad = Squad::query()
                ->where('fixture_id', $fixture->id)
                ->where('team_id', $clubTeamId)
                ->where('position', 'playing_xi')
                ->orderBy('id')
                ->get();

            foreach ($squad as $index => $player) {
                BattingScore::create([
                    'innings_id' => $innings->id,
                    'match_id' => $innings->match_id,
                    'fixture_id' => $fixture->id,
                    'user_id' => $player->user_id,
                    'team_id' => $clubTeamId,
                    'batting_order' => $index + 1,
                    'dismissal_type' => 'not_out',
                ]);
            }

            return;
        }

        foreach (array_values($fixture->opponentPlayers() ?? []) as $index => $player) {
            BattingScore::create([
                'innings_id' => $innings->id,
                'match_id' => $innings->match_id,
                'fixture_id' => $fixture->id,
                'external_player_index' => $index,
                'external_player_name' => $player['name'] ?? "Player {$index}",
                'batting_order' => $index + 1,
                'dismissal_type' => 'not_out',
            ]);
        }
    }

    private function setOpeners(Innings $innings, Fixture $fixture, array $openers, bool $battingIsClub, ?int $clubTeamId): void
    {
        if ($battingIsClub) {
            $strikerId = (int) $openers['striker_user_id'];
            $nonStrikerId = (int) $openers['non_striker_user_id'];

            $innings->update([
                'striker_id' => $strikerId,
                'non_striker_id' => $nonStrikerId,
            ]);

            $this->markBatterOnStrike($innings, ['user_id' => $strikerId, 'external_index' => null], true);
            $this->markBatterOnStrike($innings, ['user_id' => $nonStrikerId, 'external_index' => null], false);

            return;
        }

        $strikerIndex = (int) $openers['striker_player_index'];
        $nonStrikerIndex = (int) $openers['non_striker_player_index'];

        $innings->update([
            'external_striker_index' => $strikerIndex,
            'external_non_striker_index' => $nonStrikerIndex,
        ]);

        $this->markBatterOnStrike($innings, ['user_id' => null, 'external_index' => $strikerIndex], true);
        $this->markBatterOnStrike($innings, ['user_id' => null, 'external_index' => $nonStrikerIndex], false);
    }

    private function setOpeningBowler(Innings $innings, Fixture $fixture, array $openers, bool $battingIsClub, ?int $clubTeamId): void
    {
        if ($battingIsClub) {
            $this->changeBowler($innings->match, [
                'player_index' => (int) $openers['opening_bowler_player_index'],
            ]);

            return;
        }

        $this->changeBowler($innings->match, [
            'user_id' => (int) $openers['opening_bowler_user_id'],
        ]);
    }

    private function resolvePlayerRef(array $input, Fixture $fixture, bool $isClubSide): array
    {
        if ($isClubSide) {
            return [
                'user_id' => isset($input['user_id']) ? (int) $input['user_id'] : null,
                'external_index' => null,
                'name' => null,
            ];
        }

        $index = isset($input['player_index']) ? (int) $input['player_index'] : null;
        $players = array_values($fixture->opponentPlayers() ?? []);

        return [
            'user_id' => null,
            'external_index' => $index,
            'name' => $index !== null ? ($players[$index]['name'] ?? null) : null,
        ];
    }

    private function currentStrikerRef(Innings $innings): array
    {
        return [
            'user_id' => $innings->striker_id,
            'external_index' => $innings->external_striker_index,
        ];
    }

    private function currentNonStrikerRef(Innings $innings): array
    {
        return [
            'user_id' => $innings->non_striker_id,
            'external_index' => $innings->external_non_striker_index,
        ];
    }

    private function currentBowlerRef(Innings $innings): array
    {
        return [
            'user_id' => $innings->current_bowler_id,
            'external_index' => $innings->external_bowler_index,
        ];
    }

    private function markBatterOnStrike(Innings $innings, array $ref, bool $onStrike): void
    {
        $query = BattingScore::query()->where('innings_id', $innings->id);

        if ($ref['user_id']) {
            $query->where('user_id', $ref['user_id']);
        } else {
            $query->where('external_player_index', $ref['external_index']);
        }

        $score = $query->first();

        if ($score) {
            BattingScore::where('innings_id', $innings->id)->update(['is_on_strike' => false]);
            $score->update([
                'is_on_strike' => $onStrike,
                'has_batted' => true,
                'started_at' => $score->started_at ?? now(),
            ]);
        }
    }

    private function recordWicket(BallEvent $ball, Innings $innings, Matchs $match, Fixture $fixture, array $wicketData): Wicket
    {
        $strikerRef = $this->currentStrikerRef($innings);
        $bowlerRef = $this->currentBowlerRef($innings);
        $opponentPlayers = array_values($fixture->opponentPlayers() ?? []);

        $wicket = Wicket::create([
            'ball_event_id' => $ball->id,
            'innings_id' => $innings->id,
            'match_id' => $match->id,
            'fixture_id' => $fixture->id,
            'dismissed_batter_id' => $strikerRef['user_id'],
            'external_dismissed_batter_index' => $strikerRef['external_index'],
            'external_dismissed_batter_name' => $strikerRef['external_index'] !== null
                ? ($opponentPlayers[$strikerRef['external_index']]['name'] ?? null)
                : null,
            'dismissal_type' => $wicketData['dismissal_type'] ?? 'bowled',
            'bowler_id' => $bowlerRef['user_id'],
            'external_bowler_index' => $bowlerRef['external_index'],
            'fielder_one_id' => $wicketData['fielder_one_user_id'] ?? null,
            'external_fielder_one_index' => $wicketData['fielder_one_player_index'] ?? null,
            'fielder_two_id' => $wicketData['fielder_two_user_id'] ?? null,
            'external_fielder_two_index' => $wicketData['fielder_two_player_index'] ?? null,
            'runs_at_dismissal' => BattingScore::query()
                ->where('innings_id', $innings->id)
                ->when($strikerRef['user_id'], fn ($q) => $q->where('user_id', $strikerRef['user_id']))
                ->when(!$strikerRef['user_id'], fn ($q) => $q->where('external_player_index', $strikerRef['external_index']))
                ->value('runs') ?? 0,
            'description' => $wicketData['description'] ?? null,
        ]);

        $scoreQuery = BattingScore::query()->where('innings_id', $innings->id);
        if ($strikerRef['user_id']) {
            $scoreQuery->where('user_id', $strikerRef['user_id']);
        } else {
            $scoreQuery->where('external_player_index', $strikerRef['external_index']);
        }

        $scoreQuery->update([
            'is_out' => true,
            'is_on_strike' => false,
            'dismissal_type' => $wicket->dismissal_type,
            'wicket_id' => $wicket->id,
            'dismissed_at' => now(),
        ]);

        return $wicket;
    }

    private function applyBallToInnings(Innings $innings, string $eventType, int $totalRuns, int $extrasRuns, bool $isLegal, bool $isWicket): void
    {
        $updates = [
            'runs' => $innings->runs + $totalRuns,
            'extras_total' => $innings->extras_total + $extrasRuns,
        ];

        if ($isWicket) {
            $updates['wickets'] = $innings->wickets + 1;
        }

        if ($isLegal) {
            $updates['legal_deliveries'] = $innings->legal_deliveries + 1;
            $completedOvers = intdiv($updates['legal_deliveries'], 6);
            $ballsInOver = $updates['legal_deliveries'] % 6;
            $updates['overs'] = $completedOvers + ($ballsInOver / 10);
        }

        if ($eventType === 'wide') {
            $updates['wides'] = $innings->wides + 1;
        } elseif ($eventType === 'no_ball') {
            $updates['no_balls'] = $innings->no_balls + 1;
        } elseif ($eventType === 'bye') {
            $updates['byes'] = $innings->byes + $extrasRuns;
        } elseif ($eventType === 'leg_bye') {
            $updates['leg_byes'] = $innings->leg_byes + $extrasRuns;
        } elseif ($eventType === 'penalty') {
            $updates['penalty_runs'] = $innings->penalty_runs + $extrasRuns;
        }

        $innings->update($updates);
        $innings->refresh();
    }

    private function applyBallToBattingScore(Innings $innings, array $strikerRef, int $runsScored, bool $isLegal, bool $isFour, bool $isSix, bool $isWicket): void
    {
        if ($isWicket) {
            return;
        }

        $query = BattingScore::query()->where('innings_id', $innings->id);

        if ($strikerRef['user_id']) {
            $query->where('user_id', $strikerRef['user_id']);
        } else {
            $query->where('external_player_index', $strikerRef['external_index']);
        }

        $score = $query->first();

        if (!$score) {
            return;
        }

        $score->update([
            'runs' => $score->runs + $runsScored,
            'balls_faced' => $isLegal ? $score->balls_faced + 1 : $score->balls_faced,
            'fours' => $isFour ? $score->fours + 1 : $score->fours,
            'sixes' => $isSix ? $score->sixes + 1 : $score->sixes,
            'has_batted' => true,
        ]);
    }

    private function applyBallToBowlingFigure(Innings $innings, array $bowlerRef, int $totalRuns, string $eventType, bool $isWicket, ?Wicket $wicket): void
    {
        $query = BowlingFigure::query()->where('innings_id', $innings->id)->where('is_current_bowler', true);

        if ($bowlerRef['user_id']) {
            $query->where('user_id', $bowlerRef['user_id']);
        } else {
            $query->where('external_player_index', $bowlerRef['external_index']);
        }

        $figure = $query->first();

        if (!$figure) {
            return;
        }

        $ballsBowled = $figure->balls_bowled;
        if (!in_array($eventType, ['wide', 'no_ball'], true)) {
            $ballsBowled++;
        }

        $completedOvers = intdiv($ballsBowled, 6);
        $ballsInOver = $ballsBowled % 6;

        $figure->update([
            'balls_bowled' => $ballsBowled,
            'overs' => $completedOvers + ($ballsInOver / 10),
            'runs_conceded' => $figure->runs_conceded + $totalRuns,
            'wickets' => $isWicket ? $figure->wickets + 1 : $figure->wickets,
            'wides_bowled' => $eventType === 'wide' ? $figure->wides_bowled + 1 : $figure->wides_bowled,
            'no_balls_bowled' => $eventType === 'no_ball' ? $figure->no_balls_bowled + 1 : $figure->no_balls_bowled,
        ]);
    }

    private function advanceMatchCursor(Matchs $match, Innings $innings, bool $isLegal, int $runsScored, bool $isWicket): void
    {
        if (!$isLegal) {
            return;
        }

        $ballNumber = $match->current_ball_number + 1;
        $overNumber = $match->current_over_number;

        if ($ballNumber >= 6) {
            $overNumber++;
            $ballNumber = 0;
            if (!$isWicket) {
                $this->swapStrike($innings);
            }
            $this->finalizeOverSummary($innings, $match, $overNumber - 1);
        } elseif ($runsScored % 2 === 1 && !$isWicket) {
            $this->swapStrike($innings);
        }

        $match->update([
            'current_over_number' => $overNumber,
            'current_ball_number' => $ballNumber,
            'total_legal_deliveries' => $match->total_legal_deliveries + 1,
        ]);
    }

    private function swapStrike(Innings $innings): void
    {
        $strikerId = $innings->striker_id;
        $nonStrikerId = $innings->non_striker_id;
        $extStriker = $innings->external_striker_index;
        $extNonStriker = $innings->external_non_striker_index;

        $innings->update([
            'striker_id' => $nonStrikerId,
            'non_striker_id' => $strikerId,
            'external_striker_index' => $extNonStriker,
            'external_non_striker_index' => $extStriker,
        ]);

        BattingScore::where('innings_id', $innings->id)->update(['is_on_strike' => false]);

        $strikerRef = $this->currentStrikerRef($innings->fresh());
        $nonStrikerRef = $this->currentNonStrikerRef($innings);

        $this->markBatterOnStrike($innings, $strikerRef, true);
        $this->markBatterOnStrike($innings, $nonStrikerRef, false);
    }

    private function finalizeOverSummary(Innings $innings, Matchs $match, int $overNumber): void
    {
        $balls = $innings->ballEvents()
            ->where('over_number', $overNumber)
            ->orderBy('ball_sequence')
            ->get()
            ->map(fn (BallEvent $ball) => $ball->display_text)
            ->all();

        $bowlerRef = $this->currentBowlerRef($innings);

        OverSummary::updateOrCreate(
            [
                'innings_id' => $innings->id,
                'over_number' => $overNumber,
            ],
            [
                'match_id' => $match->id,
                'bowler_id' => $bowlerRef['user_id'],
                'external_bowler_index' => $bowlerRef['external_index'],
                'external_bowler_name' => $bowlerRef['external_index'] !== null
                    ? ($innings->fixture->opponentPlayers()[$bowlerRef['external_index']]['name'] ?? null)
                    : null,
                'bowling_team_id' => $innings->bowling_team_id,
                'runs' => $innings->ballEvents()->where('over_number', $overNumber)->sum('total_runs'),
                'wickets' => $innings->ballEvents()->where('over_number', $overNumber)->where('is_wicket_ball', true)->count(),
                'extras' => $innings->ballEvents()->where('over_number', $overNumber)->sum('extras_runs'),
                'is_maiden' => $innings->ballEvents()->where('over_number', $overNumber)->sum('total_runs') === 0,
                'balls' => $balls,
            ]
        );
    }

    private function syncFixtureScore(Fixture $fixture, Innings $innings): void
    {
        $isClubHome = $fixture->clubPlaysHome();
        $clubBattingFirst = $innings->innings_number === 1
            ? $innings->batting_is_club
            : !$innings->batting_is_club;

        $fieldPrefix = ($isClubHome && $clubBattingFirst) || (!$isClubHome && !$clubBattingFirst)
            ? 'home'
            : 'away';

        $fixture->update([
            "{$fieldPrefix}_team_runs" => $innings->runs,
            "{$fieldPrefix}_team_wickets" => $innings->wickets,
            "{$fieldPrefix}_team_overs" => $innings->overs,
        ]);
    }

    private function checkInningsEnd(Innings $innings, Matchs $match, Fixture $fixture): void
    {
        if ($innings->isTargetAchieved()) {
            $this->endInnings($match, 'target_achieved');
        } elseif ($innings->isAllOut()) {
            $this->endInnings($match, 'all_out');
        } elseif ($innings->isOversComplete()) {
            $this->endInnings($match, 'overs_completed');
        }
    }

    private function formatClubSquadPlayer(Squad $squad): array
    {
        $player = $squad->player;
        $name = trim(($player?->first_name ?? '') . ' ' . ($player?->last_name ?? ''));

        return [
            'user_id' => $squad->user_id,
            'name' => $name ?: $player?->email,
            'position' => $squad->position,
            'jersey_number' => $squad->jersey_number,
            'is_captain' => (bool) $squad->is_captain,
            'is_wicket_keeper' => (bool) $squad->is_wicket_keeper,
        ];
    }

    private function formatInningsLive(Innings $innings): array
    {
        return [
            'id' => $innings->id,
            'innings_number' => $innings->innings_number,
            'batting_is_club' => $innings->batting_is_club,
            'runs' => $innings->runs,
            'wickets' => $innings->wickets,
            'overs' => $innings->overs,
            'score_display' => $innings->score_display,
            'target' => $innings->target,
            'run_rate' => $innings->run_rate,
            'required_run_rate' => $innings->required_run_rate,
            'result' => $innings->result,
            'striker_id' => $innings->striker_id,
            'non_striker_id' => $innings->non_striker_id,
            'external_striker_index' => $innings->external_striker_index,
            'external_non_striker_index' => $innings->external_non_striker_index,
            'current_bowler_id' => $innings->current_bowler_id,
            'external_bowler_index' => $innings->external_bowler_index,
            'batting_scores' => $innings->battingScores,
            'bowling_figures' => $innings->bowlingFigures,
        ];
    }
}
