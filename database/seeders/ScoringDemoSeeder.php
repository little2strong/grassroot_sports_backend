<?php

namespace Database\Seeders;

use App\Models\BallEvent;
use App\Models\BattingScore;
use App\Models\BowlingFigure;
use App\Models\Fixture;
use App\Models\Innings;
use App\Models\MatchScorer;
use App\Models\Matchs;
use App\Models\MatchSummary;
use App\Models\OverSummary;
use App\Models\Squad;
use App\Models\Team;
use App\Models\User;
use App\Models\Wicket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoringDemoSeeder extends Seeder
{
    public function run(): void
    {
        $fixture = Fixture::where('status', 'completed')
            ->where('away_opponent_name', 'Nottingham Navigators')
            ->first();

        if (!$fixture) {
            $this->command->warn('No completed Nottingham Navigators fixture found. Skipping scoring seeder.');
            return;
        }

        if ($fixture->match) {
            $this->command->info('Scoring data already exists for this fixture. Skipping.');
            return;
        }

        $clubTeam = Team::where('slug', 'first-xi')->first();
        $owner = User::where('email', 'club@cricket-os.test')->first();

        if (!$clubTeam || !$owner) {
            $this->command->warn('Club team or owner not found. Run ClubDemoSeeder first.');
            return;
        }

        $opponentPlayers = $this->getOpponentPlayers();
        $fixture->update([
            'away_opponent_players' => $opponentPlayers,
            'toss_winner_side' => 'club',
            'toss_decision' => 'bat',
            'toss_winner_team_id' => $clubTeam->id,
            'scorer_user_id' => $owner->id,
            'scorer_assigned_at' => now(),
        ]);

        $this->seedSquads($fixture, $clubTeam, $owner);

        DB::transaction(function () use ($fixture, $clubTeam, $owner, $opponentPlayers) {
            $startedAt = $fixture->started_at ?? now()->subDays(7)->setTime(14, 0);
            $innings1End = (clone $startedAt)->addHours(2)->addMinutes(15);
            $innings2Start = (clone $innings1End)->addMinutes(5);
            $completedAt = (clone $innings2Start)->addHours(2)->addMinutes(10);

            $fixture->update([
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ]);

            $match = Matchs::create([
                'fixture_id' => $fixture->id,
                'current_innings_number' => 2,
                'current_over_number' => 20,
                'current_ball_number' => 0,
                'total_legal_deliveries' => 240,
                'is_paused' => false,
                'first_innings_id' => null,
                'second_innings_id' => null,
                'current_powerplay' => 'none',
                'powerplay_config' => null,
            ]);

            MatchScorer::create([
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'user_id' => $owner->id,
                'role' => 'primary_scorer',
                'assigned_by' => $owner->id,
                'assigned_at' => $startedAt,
            ]);

            $innings1 = Innings::create([
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'batting_team_id' => $clubTeam->id,
                'bowling_team_id' => null,
                'innings_number' => 1,
                'runs' => 178,
                'wickets' => 6,
                'overs' => 20.0,
                'legal_deliveries' => 120,
                'extras_total' => 12,
                'wides' => 5,
                'no_balls' => 1,
                'byes' => 3,
                'leg_byes' => 3,
                'penalty_runs' => 0,
                'target' => null,
                'total_batters' => 11,
                'batting_is_club' => true,
                'bowling_is_club' => false,
                'result' => 'overs_completed',
                'result_note' => 'Innings completed after 20 overs',
                'started_at' => $startedAt,
                'ended_at' => $innings1End,
            ]);

            $match->update(['first_innings_id' => $innings1->id]);

            $innings2 = Innings::create([
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'batting_team_id' => null,
                'bowling_team_id' => $clubTeam->id,
                'innings_number' => 2,
                'runs' => 172,
                'wickets' => 9,
                'overs' => 20.0,
                'legal_deliveries' => 120,
                'extras_total' => 10,
                'wides' => 4,
                'no_balls' => 0,
                'byes' => 4,
                'leg_byes' => 2,
                'penalty_runs' => 0,
                'target' => 179,
                'total_batters' => 11,
                'batting_is_club' => false,
                'bowling_is_club' => true,
                'result' => 'overs_completed',
                'result_note' => 'Innings completed after 20 overs — 6 runs short',
                'started_at' => $innings2Start,
                'ended_at' => $completedAt,
            ]);

            $match->update(['second_innings_id' => $innings2->id]);

            $squadUserIds = Squad::where('fixture_id', $fixture->id)
                ->where('team_id', $clubTeam->id)
                ->where('position', 'playing_xi')
                ->orderBy('id')
                ->pluck('user_id')
                ->toArray();

            $players = User::whereIn('id', $squadUserIds)
                ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $squadUserIds)) . ')')
                ->get();

            $this->seedFirstInningsData($innings1, $match, $fixture, $clubTeam, $players, $opponentPlayers);
            $this->seedSecondInningsData($innings2, $match, $fixture, $clubTeam, $squadUserIds, $opponentPlayers);
            $this->seedMatchSummary($fixture, $match, $innings1, $innings2, $clubTeam);
        });

        $this->command->info('Scoring demo data seeded successfully for the completed fixture.');
    }

    private function seedSquads(Fixture $fixture, Team $team, User $owner): void
    {
        if ($fixture->squads()->where('team_id', $team->id)->exists()) {
            return;
        }

        $players = User::whereHas('clubMembers', fn ($q) => $q->where('club_id', $team->club_id)->where('role', 'player')->where('status', 'active'))
            ->where('is_active', true)
            ->limit(11)
            ->orderBy('id')
            ->get();

        foreach ($players as $index => $player) {
            Squad::create([
                'fixture_id' => $fixture->id,
                'team_id' => $team->id,
                'user_id' => $player->id,
                'position' => 'playing_xi',
                'jersey_number' => $index + 1,
                'is_captain' => $index === 0,
                'is_wicket_keeper' => $index === 3,
                'added_by' => $owner->id,
            ]);
        }
    }

    private function seedFirstInningsData(Innings $innings, Matchs $match, Fixture $fixture, Team $clubTeam, $players, array $opponentPlayers): void
    {
        $battingData = [
            [1, 52, 38, 7, 1, true,  'caught'],
            [2, 23, 18, 3, 0, true,  'lbw'],
            [3,  8, 12, 1, 0, true,  'bowled'],
            [4, 31, 22, 4, 0, true,  'caught'],
            [5, 15, 20, 1, 0, true,  'run_out'],
            [6, 28, 16, 2, 2, false, 'not_out'],
            [7,  0,  3, 0, 0, true,  'caught'],
            [8, 12,  8, 1, 1, false, 'not_out'],
            [9,  0,  0, 0, 0, false, 'not_out'],
            [10, 0,  0, 0, 0, false, 'not_out'],
            [11, 0,  0, 0, 0, false, 'not_out'],
        ];

        $wicketsData = [
            [1, 'caught',          2, 5, 45],
            [2, 'lbw',             0, null, 18],
            [3, 'bowled',          1, null, 5],
            [4, 'caught',          3, 4, 28],
            [5, 'run_out',         null, 8, 14],
            [7, 'caught',          4, 3, 0],
        ];

        $wicketMap = [];
        foreach ($wicketsData as $w) {
            $wicketMap[$w[0]] = $w;
        }

        foreach ($battingData as $idx => $bat) {
            $player = $players->get($idx);
            if (!$player) continue;

            $w = $wicketMap[$bat[0]] ?? null;

            BattingScore::create([
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'user_id' => $player->id,
                'team_id' => $clubTeam->id,
                'batting_order' => $bat[0],
                'is_on_strike' => false,
                'has_batted' => $bat[2] > 0,
                'runs' => $bat[1],
                'balls_faced' => $bat[2],
                'fours' => $bat[3],
                'sixes' => $bat[4],
                'is_out' => $bat[5],
                'dismissal_type' => $bat[6],
                'dismissed_by_bowler_id' => null,
                'caught_by_fielder_id' => null,
                'run_out_by_fielder_one_id' => null,
                'dismissal_description' => $w ? $this->buildDismissalDescription($w, $opponentPlayers) : null,
                'started_at' => $innings->started_at,
                'dismissed_at' => $bat[5] && $bat[2] > 0
                    ? (clone $innings->started_at)->addMinutes((int) ($bat[2] * 1.2))
                    : null,
            ]);
        }

        $bowlingData = [
            [0, 4.0, 24, 0, 32, 1, 2, 0],
            [1, 4.0, 24, 1, 25, 1, 0, 0],
            [2, 4.0, 24, 0, 38, 1, 1, 0],
            [3, 4.0, 24, 0, 41, 1, 1, 1],
            [4, 4.0, 24, 0, 30, 1, 1, 0],
        ];

        foreach ($bowlingData as $bowl) {
            BowlingFigure::create([
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'user_id' => null,
                'team_id' => null,
                'external_player_index' => $bowl[0],
                'external_player_name' => $opponentPlayers[$bowl[0]]['name'],
                'overs' => $bowl[1],
                'balls_bowled' => $bowl[2],
                'maidens' => $bowl[3],
                'runs_conceded' => $bowl[4],
                'wickets' => $bowl[5],
                'wides_bowled' => $bowl[6],
                'no_balls_bowled' => $bowl[7],
                'is_current_bowler' => false,
            ]);
        }

        foreach ($wicketsData as $wData) {
            $dismissedPlayer = $players->get($wData[0] - 1);
            if (!$dismissedPlayer) continue;

            Wicket::create([
                'ball_event_id' => null,
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'dismissed_batter_id' => $dismissedPlayer->id,
                'dismissal_type' => $wData[1],
                'bowler_id' => null,
                'external_bowler_index' => $wData[2],
                'fielder_one_id' => null,
                'external_fielder_one_index' => $wData[3],
                'runs_at_dismissal' => $wData[4],
                'description' => null,
            ]);
        }

        $this->generateBallEventsAndOverSummaries($innings, $match, $fixture, $players, $opponentPlayers, $battingData, $wicketsData, true);
    }

    private function seedSecondInningsData(Innings $innings, Matchs $match, Fixture $fixture, Team $clubTeam, array $squadUserIds, array $opponentPlayers): void
    {
        $oppBattingData = [
            [1,  35, 28, 5, 0, true,  'bowled'],
            [2,  18, 14, 2, 0, true,  'caught'],
            [3,  42, 30, 4, 2, true,  'caught'],
            [4,   5,  8, 0, 0, true,  'lbw'],
            [5,  22, 18, 2, 1, true,  'stumped'],
            [6,  15, 12, 1, 0, true,  'run_out'],
            [7,   8, 10, 1, 0, true,  'caught'],
            [8,  12,  9, 0, 1, true,  'caught_and_bowled'],
            [9,   6,  5, 1, 0, false, 'not_out'],
            [10,  3,  4, 0, 0, true,  'run_out'],
            [11,  0,  2, 0, 0, false, 'not_out'],
        ];

        $oppWicketsData = [
            [1,  'bowled',           2, null, 32],
            [2,  'caught',           0, 3, 14],
            [3,  'caught',           1, 6, 38],
            [4,  'lbw',              2, null, 4],
            [5,  'stumped',          4, 3, 20],
            [6,  'run_out',          null, 1, 13],
            [7,  'caught',           0, 0, 7],
            [8,  'caught_and_bowled', 1, null, 11],
            [10, 'run_out',          null, 2, 2],
        ];

        $wicketMap = [];
        foreach ($oppWicketsData as $w) {
            $wicketMap[$w[0]] = $w;
        }

        foreach ($oppBattingData as $idx => $bat) {
            $w = $wicketMap[$bat[0]] ?? null;

            $bowlerUserId = null;
            $fielderUserId = null;

            if ($w) {
                $bowlerUserId = $w[2] !== null ? ($squadUserIds[$w[2]] ?? null) : null;
                $fielderUserId = $w[3] !== null && in_array($w[1], ['caught', 'stumped'])
                    ? ($squadUserIds[$w[3]] ?? null) : null;
            }

            BattingScore::create([
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'user_id' => null,
                'team_id' => null,
                'external_player_index' => $idx,
                'external_player_name' => $opponentPlayers[$idx]['name'],
                'batting_order' => $bat[0],
                'is_on_strike' => false,
                'has_batted' => $bat[2] > 0,
                'runs' => $bat[1],
                'balls_faced' => $bat[2],
                'fours' => $bat[3],
                'sixes' => $bat[4],
                'is_out' => $bat[5],
                'dismissal_type' => $bat[6],
                'dismissed_by_bowler_id' => $bowlerUserId,
                'caught_by_fielder_id' => in_array($bat[6], ['caught', 'stumped', 'caught_and_bowled']) ? $fielderUserId : null,
                'dismissal_description' => null,
                'started_at' => $innings->started_at,
                'dismissed_at' => $bat[5] && $bat[2] > 0
                    ? (clone $innings->started_at)->addMinutes((int) ($bat[2] * 1.2))
                    : null,
            ]);
        }

        $clubBowlingData = [
            [0, 4.0, 24, 0, 28, 2, 1, 0],
            [1, 4.0, 24, 1, 30, 2, 1, 0],
            [2, 4.0, 24, 0, 35, 1, 1, 0],
            [4, 4.0, 24, 0, 38, 1, 0, 0],
            [5, 3.0, 18, 0, 25, 1, 1, 0],
            [9, 1.0,  6, 0, 16, 1, 0, 0],
        ];

        foreach ($clubBowlingData as $bowl) {
            $userId = $squadUserIds[$bowl[0]] ?? null;
            if (!$userId) continue;

            BowlingFigure::create([
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'user_id' => $userId,
                'team_id' => $clubTeam->id,
                'external_player_index' => null,
                'external_player_name' => null,
                'overs' => $bowl[1],
                'balls_bowled' => $bowl[2],
                'maidens' => $bowl[3],
                'runs_conceded' => $bowl[4],
                'wickets' => $bowl[5],
                'wides_bowled' => $bowl[6],
                'no_balls_bowled' => $bowl[7],
                'is_current_bowler' => false,
            ]);
        }

        foreach ($oppWicketsData as $wData) {
            $bowlerUserId = $wData[2] !== null ? ($squadUserIds[$wData[2]] ?? null) : null;
            $fielderUserId = $wData[3] !== null ? ($squadUserIds[$wData[3]] ?? null) : null;

            Wicket::create([
                'ball_event_id' => null,
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'dismissed_batter_id' => null,
                'external_dismissed_batter_index' => $wData[0] - 1,
                'external_dismissed_batter_name' => $opponentPlayers[$wData[0] - 1]['name'],
                'dismissal_type' => $wData[1],
                'bowler_id' => $bowlerUserId,
                'fielder_one_id' => $fielderUserId,
                'runs_at_dismissal' => $wData[4],
                'description' => null,
            ]);
        }

        $players = User::whereIn('id', $squadUserIds)
            ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $squadUserIds)) . ')')
            ->get();

        $this->generateBallEventsAndOverSummaries($innings, $match, $fixture, $players, $opponentPlayers, $oppBattingData, $oppWicketsData, false);
    }

    private function generateBallEventsAndOverSummaries(
        Innings $innings, Matchs $match, Fixture $fixture,
        $players, array $opponentPlayers, array $battingData,
        array $wicketsData, bool $clubBatting
    ): void {
        $allBalls = $this->preGenerateInningsBalls($battingData, $wicketsData);

        $ballSequence = 0;
        $legalBallSequence = 0;
        $strikerOrder = 1;
        $nonStrikerOrder = 2;
        $nextBatterOrder = 3;

        $bowlerRotation = $clubBatting
            ? [0, 1, 2, 3, 4, 1, 0, 3, 2, 4, 0, 2, 3, 1, 4, 3, 0, 4, 2, 1]
            : [0, 1, 2, 4, 5, 1, 0, 2, 4, 9, 5, 2, 0, 1, 4, 9, 2, 5, 0, 1];

        $legalBallIdx = 0;

        for ($over = 0; $over < 20; $over++) {
            $overEvents = [];
            $currentBowlerRef = $bowlerRotation[$over];

            for ($ballInOver = 0; $ballInOver < 6; $ballInOver++) {
                if ($legalBallIdx >= count($allBalls)) break;

                $ballData = $allBalls[$legalBallIdx];
                $legalBallIdx++;

                // Inject extras at specific points for realism
                if ($over === 3 && $ballInOver === 2) {
                    $extraEvent = $this->createBallEvent(
                        $innings, $match, $fixture, $over, $ballInOver + 1,
                        ++$ballSequence, $legalBallSequence,
                        $strikerOrder, $nonStrikerOrder, $currentBowlerRef,
                        $clubBatting, [
                            'event_type' => 'wide', 'runs_scored' => 0, 'total_runs' => 1,
                            'is_four' => false, 'is_six' => false, 'extras_type' => 'wide',
                            'extras_runs' => 1, 'is_legal' => false, 'is_wicket' => false,
                        ]
                    );
                    $overEvents[] = $extraEvent;
                }

                if ($over === 8 && $ballInOver === 4) {
                    $extraEvent = $this->createBallEvent(
                        $innings, $match, $fixture, $over, $ballInOver + 1,
                        ++$ballSequence, ++$legalBallSequence,
                        $strikerOrder, $nonStrikerOrder, $currentBowlerRef,
                        $clubBatting, [
                            'event_type' => 'leg_bye', 'runs_scored' => 0, 'total_runs' => 2,
                            'is_four' => false, 'is_six' => false, 'extras_type' => 'leg_bye',
                            'extras_runs' => 2, 'is_legal' => true, 'is_wicket' => false,
                        ]
                    );
                    $overEvents[] = $extraEvent;
                    continue;
                }

                $isWicket = false;
                $batterBallsUsed = 0;
                for ($b = 0; $b < $legalBallIdx; $b++) {
                    if ($allBalls[$b]['is_four'] || $allBalls[$b]['is_six'] || $allBalls[$b]['runs'] > 0 || true) {
                        $batterBallsUsed++;
                    }
                }
                $batterExpectedBalls = $battingData[$strikerOrder - 1][2] ?? 999;
                if ($batterExpectedBalls > 0 && $batterBallsUsed >= $batterExpectedBalls) {
                    $isWicket = true;
                }

                $ballSequence++;
                $legalBallSequence++;

                $event = $this->createBallEvent(
                    $innings, $match, $fixture, $over, $ballInOver + 1,
                    $ballSequence, $legalBallSequence,
                    $strikerOrder, $nonStrikerOrder, $currentBowlerRef,
                    $clubBatting, [
                        'event_type' => $isWicket ? 'wicket' : 'run',
                        'runs_scored' => $isWicket ? 0 : $ballData['runs'],
                        'total_runs' => $isWicket ? 0 : $ballData['runs'],
                        'is_four' => $ballData['is_four'],
                        'is_six' => $ballData['is_six'],
                        'extras_type' => null, 'extras_runs' => 0,
                        'is_legal' => true, 'is_wicket' => $isWicket,
                    ]
                );
                $overEvents[] = $event;

                if ($isWicket) {
                    while ($nextBatterOrder <= 11 && $battingData[$nextBatterOrder - 1][2] === 0 && !$battingData[$nextBatterOrder - 1][5]) {
                        $nextBatterOrder++;
                    }
                    if ($nextBatterOrder <= 11) {
                        $strikerOrder = $nextBatterOrder;
                        $nextBatterOrder++;
                    }
                } elseif ($ballData['runs'] % 2 === 1) {
                    [$strikerOrder, $nonStrikerOrder] = [$nonStrikerOrder, $strikerOrder];
                }
            }

            [$strikerOrder, $nonStrikerOrder] = [$nonStrikerOrder, $strikerOrder];

            $displayBalls = collect($overEvents)->map(fn (BallEvent $e) => $e->display_text)->values()->all();

            OverSummary::create([
                'innings_id' => $innings->id,
                'match_id' => $match->id,
                'over_number' => $over,
                'bowler_id' => $clubBatting ? null : ($players->get($currentBowlerRef)?->id ?? null),
                'external_bowler_index' => $clubBatting ? $currentBowlerRef : null,
                'external_bowler_name' => $clubBatting ? ($opponentPlayers[$currentBowlerRef]['name'] ?? null) : null,
                'bowling_team_id' => $innings->bowling_team_id,
                'runs' => collect($overEvents)->sum('total_runs'),
                'wickets' => collect($overEvents)->where('is_wicket_ball', true)->count(),
                'extras' => collect($overEvents)->sum('extras_runs'),
                'is_maiden' => collect($overEvents)->where('is_legal_delivery', true)->count() >= 6
                    && collect($overEvents)->sum('total_runs') === 0,
                'balls' => $displayBalls,
            ]);
        }
    }

    private function createBallEvent(
        Innings $innings, Matchs $match, Fixture $fixture,
        int $overNumber, int $ballNumber, int $ballSequence, int $legalBallSequence,
        int $strikerOrder, int $nonStrikerOrder, int $bowlerRef,
        bool $clubBatting, array $data
    ): BallEvent {
        if ($clubBatting) {
            $players = User::whereHas('clubMembers', fn ($q) => $q->where('club_id', $innings->batting_team_id)->where('role', 'player'))
                ->where('is_active', true)->orderBy('id')->limit(11)->get();

            return BallEvent::create([
                'innings_id' => $innings->id, 'match_id' => $match->id, 'fixture_id' => $fixture->id,
                'over_number' => $overNumber, 'ball_number' => $ballNumber,
                'ball_sequence' => $ballSequence, 'legal_ball_sequence' => $data['is_legal'] ? $legalBallSequence : $legalBallSequence,
                'striker_id' => $players->get($strikerOrder - 1)?->id ?? null,
                'non_striker_id' => $players->get($nonStrikerOrder - 1)?->id ?? null,
                'bowler_id' => null,
                'external_striker_index' => null, 'external_non_striker_index' => null,
                'external_bowler_index' => $bowlerRef,
                'batting_team_id' => $innings->batting_team_id, 'bowling_team_id' => null,
                'event_type' => $data['event_type'], 'runs_scored' => $data['runs_scored'], 'total_runs' => $data['total_runs'],
                'is_boundary_four' => $data['is_four'], 'is_boundary_six' => $data['is_six'],
                'extras_type' => $data['extras_type'], 'extras_runs' => $data['extras_runs'],
                'is_legal_delivery' => $data['is_legal'], 'is_wicket_ball' => $data['is_wicket'],
                'commentary' => null, 'is_synced' => true,
            ]);
        }

        $squadUserIds = Squad::where('fixture_id', $fixture->id)
            ->where('team_id', $innings->bowling_team_id)->where('position', 'playing_xi')
            ->orderBy('id')->pluck('user_id')->toArray();

        return BallEvent::create([
            'innings_id' => $innings->id, 'match_id' => $match->id, 'fixture_id' => $fixture->id,
            'over_number' => $overNumber, 'ball_number' => $ballNumber,
            'ball_sequence' => $ballSequence, 'legal_ball_sequence' => $data['is_legal'] ? $legalBallSequence : $legalBallSequence,
            'striker_id' => null, 'non_striker_id' => null,
            'bowler_id' => $squadUserIds[$bowlerRef] ?? null,
            'external_striker_index' => $strikerOrder - 1, 'external_non_striker_index' => $nonStrikerOrder - 1,
            'external_bowler_index' => null,
            'batting_team_id' => null, 'bowling_team_id' => $innings->bowling_team_id,
            'event_type' => $data['event_type'], 'runs_scored' => $data['runs_scored'], 'total_runs' => $data['total_runs'],
            'is_boundary_four' => $data['is_four'], 'is_boundary_six' => $data['is_six'],
            'extras_type' => $data['extras_type'], 'extras_runs' => $data['extras_runs'],
            'is_legal_delivery' => $data['is_legal'], 'is_wicket_ball' => $data['is_wicket'],
            'commentary' => null, 'is_synced' => true,
        ]);
    }

    private function preGenerateInningsBalls(array $battingData, array $wicketsData): array
    {
        $balls = [];
        foreach ($battingData as $idx => $bat) {
            $runs = $bat[1];
            $ballsFaced = $bat[2];
            $fours = $bat[3];
            $sixes = $bat[4];
            $isOut = $bat[5];

            if ($ballsFaced === 0) continue;

            $foursLeft = $fours;
            $sixesLeft = $sixes;
            $runsLeft = $runs;

            for ($b = 0; $b < $ballsFaced; $b++) {
                $isLastBall = ($b === $ballsFaced - 1);

                if ($isLastBall && $isOut) {
                    $balls[] = ['runs' => 0, 'is_four' => false, 'is_six' => false];
                    continue;
                }

                if ($sixesLeft > 0 && $runsLeft >= 6 && ($b % 7 === 3 || $b % 5 === 0)) {
                    $balls[] = ['runs' => 6, 'is_four' => false, 'is_six' => true];
                    $runsLeft -= 6;
                    $sixesLeft--;
                } elseif ($foursLeft > 0 && $runsLeft >= 4 && ($b % 6 === 1 || $b % 4 === 2)) {
                    $balls[] = ['runs' => 4, 'is_four' => true, 'is_six' => false];
                    $runsLeft -= 4;
                    $foursLeft--;
                } else {
                    $remainingBalls = $ballsFaced - $b - ($isOut ? 1 : 0);
                    if ($remainingBalls > 0 && $runsLeft > 0) {
                        $avgRun = $runsLeft / $remainingBalls;
                        if ($avgRun >= 2) {
                            $r = min($runsLeft, mt_rand(1, 3));
                        } elseif ($avgRun >= 1) {
                            $r = $runsLeft > 0 ? (mt_rand(1, 10) <= 6 ? 1 : 0) : 0;
                        } else {
                            $r = 0;
                        }
                    } else {
                        $r = 0;
                    }
                    $balls[] = ['runs' => $r, 'is_four' => false, 'is_six' => false];
                    $runsLeft -= $r;
                }
            }

            while ($runsLeft > 0) {
                $startIdx = count($balls) - $ballsFaced;
                $endIdx = count($balls) - ($isOut ? 1 : 0);
                if ($startIdx >= $endIdx) break;
                $pickIdx = mt_rand($startIdx, max($startIdx, $endIdx - 1));
                if (isset($balls[$pickIdx]) && !$balls[$pickIdx]['is_four'] && !$balls[$pickIdx]['is_six']) {
                    $add = min($runsLeft, 3);
                    $balls[$pickIdx]['runs'] += $add;
                    $runsLeft -= $add;
                } else {
                    break;
                }
            }
        }
        return $balls;
    }

    private function buildDismissalDescription(array $wData, array $opponentPlayers): string
    {
        $bowlerName = $wData[2] !== null ? $opponentPlayers[$wData[2]]['name'] : null;
        $fielderName = $wData[3] !== null ? $opponentPlayers[$wData[3]]['name'] : null;

        return match ($wData[1]) {
            'bowled' => "b {$bowlerName}",
            'caught' => "c {$fielderName} b {$bowlerName}",
            'lbw' => "lbw b {$bowlerName}",
            'run_out' => "run out ({$fielderName})",
            'stumped' => "st {$fielderName} b {$bowlerName}",
            'caught_and_bowled' => "c & b {$bowlerName}",
            default => (string) $wData[1],
        };
    }

    private function seedMatchSummary(Fixture $fixture, Matchs $match, Innings $innings1, Innings $innings2, Team $clubTeam): void
    {
        $topScorer1 = BattingScore::where('innings_id', $innings1->id)
            ->where('has_batted', true)->orderByDesc('runs')->first();

        $bestBowler2 = BowlingFigure::where('innings_id', $innings2->id)
            ->orderByDesc('wickets')->orderBy('runs_conceded')->first();

        $topScorer2 = BattingScore::where('innings_id', $innings2->id)
            ->where('has_batted', true)->orderByDesc('runs')->first();

        $motmPlayer = $fixture->man_of_the_match_id ? User::find($fixture->man_of_the_match_id) : ($topScorer1?->player);

        MatchSummary::create([
            'fixture_id' => $fixture->id, 'match_id' => $match->id,
            'innings_one' => [
                'team_name' => $clubTeam->short_name, 'runs' => $innings1->runs,
                'wickets' => $innings1->wickets, 'overs' => $innings1->overs->toFloat(),
            ],
            'innings_two' => [
                'team_name' => $fixture->away_opponent_name, 'runs' => $innings2->runs,
                'wickets' => $innings2->wickets, 'overs' => $innings2->overs->toFloat(),
            ],
            'key_stats' => [
                'highest_score' => $topScorer1 ? [
                    'player_name' => $topScorer1->player?->full_name,
                    'runs' => $topScorer1->runs, 'balls' => $topScorer1->balls_faced,
                    'fours' => $topScorer1->fours, 'sixes' => $topScorer1->sixes, 'innings' => 1,
                ] : null,
                'best_bowling' => $bestBowler2 ? [
                    'player_name' => $bestBowler2->bowler?->full_name ?? $bestBowler2->external_player_name,
                    'overs' => $bestBowler2->overs, 'maidens' => $bestBowler2->maidens,
                    'runs' => $bestBowler2->runs_conceded, 'wickets' => $bestBowler2->wickets, 'innings' => 2,
                ] : null,
                'man_of_the_match' => $motmPlayer ? [
                    'player_name' => $motmPlayer->full_name, 'user_id' => $motmPlayer->id,
                    'reason' => 'Top score with the bat in the first innings',
                ] : null,
            ],
            'narrative' => "{$clubTeam->short_name} won the toss and elected to bat. They posted {$innings1->runs}/{$innings1->wickets} in {$innings1->overs} overs"
                . ($topScorer1 ? ", with {$topScorer1->player?->first_name} top-scoring with {$topScorer1->runs} ({$topScorer1->balls_faced})" : '')
                . ". In reply, {$fixture->away_opponent_name} finished {$innings2->runs}/{$innings2->wickets} in {$innings2->overs} overs.",
            'is_published' => true, 'published_at' => $fixture->completed_at,
        ]);
    }

    private function getOpponentPlayers(): array
    {
        return [
            ['name' => 'James Cooper'],
            ['name' => 'Ryan Phillips'],
            ['name' => 'Marcus Webb'],
            ['name' => 'Sean Palmer'],
            ['name' => 'Luke Edwards'],
            ['name' => 'Kieran Morgan'],
            ['name' => 'Bradley Cook'],
            ['name' => 'Aaron Foster'],
            ['name' => 'Declan Ross'],
            ['name' => 'Cameron Blake'],
            ['name' => 'Evan Marshall'],
        ];
    }
}
