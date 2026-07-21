<?php

namespace Database\Seeders;

use App\Models\BallEvent;
use App\Models\BattingScore;
use App\Models\BowlingFigure;
use App\Models\Fixture;
use App\Models\Innings;
use App\Models\Matchs;
use App\Models\MatchScorer;
use App\Models\OverSummary;
use App\Models\Squad;
use App\Models\Team;
use App\Models\User;
use App\Models\Wicket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LiveMatchSeeder extends Seeder
{
    public function run(): void
    {
        $fixture = Fixture::where('status', 'live')->first();

        if ($fixture) {
            $this->command->info('Live match already exists. Skipping.');
            return;
        }

        $clubTeam = Team::where('slug', 'first-xi')->first();
        $owner = User::where('email', 'club@cricket-os.test')->first();

        if (!$clubTeam || !$owner) {
            $this->command->warn('Club team or owner not found. Run ClubDemoSeeder first.');
            return;
        }

        $opponentPlayers = $this->getOpponentPlayers();

        $fixture = Fixture::create([
            'club_id' => $clubTeam->club_id,
            'home_team_id' => $clubTeam->id,
            'away_team_id' => null,
            'home_opponent_name' => null,
            'away_opponent_name' => 'Manchester Titans',
            'venue_id' => null,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now()->format('H:i'),
            'match_type' => 't20',
            'overs_per_innings' => 20,
            'ball_type' => 'leather',
            'status' => 'live',
            'is_public' => true,
            'published_at' => now()->subMinutes(30),
            'started_at' => now()->subMinutes(45),
            'toss_winner_team_id' => $clubTeam->id,
            'toss_decision' => 'bat',
            'toss_winner_side' => 'club',
            'club_plays_home' => true,
            'scorer_user_id' => $owner->id,
            'scorer_assigned_at' => now()->subMinutes(45),
            'created_by' => $owner->id,
            'home_opponent_players' => [],
            'away_opponent_players' => $opponentPlayers,
            'public_share_slug' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $this->seedSquads($fixture, $clubTeam, $owner);

        DB::transaction(function () use ($fixture, $clubTeam, $owner, $opponentPlayers) {
            $startedAt = $fixture->started_at;

            $match = Matchs::create([
                'fixture_id' => $fixture->id,
                'current_innings_number' => 1,
                'current_over_number' => 7,
                'current_ball_number' => 4,
                'total_legal_deliveries' => 120,
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

            $squadUserIds = Squad::where('fixture_id', $fixture->id)
                ->where('team_id', $clubTeam->id)
                ->where('position', 'playing_xi')
                ->orderBy('id')
                ->pluck('user_id')
                ->toArray();

            $players = User::whereIn('id', $squadUserIds)
                ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $squadUserIds)) . ')')
                ->get();

            $innings1 = Innings::create([
                'match_id' => $match->id,
                'fixture_id' => $fixture->id,
                'batting_team_id' => $clubTeam->id,
                'bowling_team_id' => null,
                'innings_number' => 1,
                'runs' => 78,
                'wickets' => 2,
                'overs' => 7.4,
                'legal_deliveries' => 44,
                'extras_total' => 6,
                'wides' => 3,
                'no_balls' => 1,
                'byes' => 2,
                'leg_byes' => 0,
                'penalty_runs' => 0,
                'target' => null,
                'total_batters' => 11,
                'batting_is_club' => true,
                'bowling_is_club' => false,
                'result' => 'in_progress',
                'result_note' => 'Match in progress',
                'started_at' => $startedAt,
                'ended_at' => null,
            ]);

            $match->update(['first_innings_id' => $innings1->id]);

            $this->seedFirstInningsData($innings1, $match, $fixture, $clubTeam, $players, $opponentPlayers);
        });

        $this->command->info('Live match seeded successfully.');
    }

    private function seedSquads(Fixture $fixture, Team $team, User $owner): void
    {
        if ($fixture->squads()->where('team_id', $team->id)->exists()) {
            return;
        }

        $players = User::whereHas('clubMemberships', fn ($q) => $q->where('club_id', $team->club_id)->where('role', 'player')->where('status', 'active'))
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
            [1, 42, 35, 4, 1, true,  'caught'],
            [2, 18, 15, 2, 0, true,  'lbw'],
            [3,  5,  8, 0, 0, true,  'bowled'],
            [4, 25, 20, 3, 0, true,  'caught'],
            [5, 12, 10, 1, 0, false, 'not_out'],
            [6,  8,  6, 0, 0, false, 'not_out'],
            [7, 15, 12, 2, 0, false, 'not_out'],
            [8,  3,  4, 0, 0, false, 'not_out'],
            [9,  0,  0, 0, 0, false, 'not_out'],
            [10, 0,  0, 0, 0, false, 'not_out'],
            [11, 0,  0, 0, 0, false, 'not_out'],
        ];

        $wicketsData = [
            [1, 'caught',          2, 4, 45],
            [2, 'lbw',             0, null, 18],
            [3, 'bowled',          1, null, 5],
            [4, 'caught',          3, 3, 28],
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
            [0, 7.4, 44, 0, 78, 2, 3, 1],
            [1, 4.0, 24, 0, 32, 1, 2, 0],
            [2, 4.0, 24, 0, 41, 1, 1, 1],
            [3, 4.0, 24, 0, 30, 1, 1, 0],
            [4, 4.0, 24, 0, 38, 1, 0, 0],
            [5, 3.0, 12, 0, 25, 1, 1, 0],
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

    private function generateBallEventsAndOverSummaries(
        Innings $innings, Matchs $match, Fixture $fixture,
        $players, array $opponentPlayers, array $battingData,
        array $wicketsData, bool $clubBatting
    ): void {
        $allBalls = $this->preGenerateInningsBalls($battingData, $wicketsData);

        $ballSequence = 145;
        $legalBallSequence = 44;
        $strikerOrder = 5;
        $nonStrikerOrder = 6;
        $nextBatterOrder = 7;

        $bowlerRotation = $clubBatting
            ? [0, 1, 2, 3, 4, 1, 0, 3, 2, 4, 0, 2, 3, 1, 4, 3, 0, 4, 2, 1]
            : [0, 1, 2, 4, 5, 1, 0, 2, 4, 9, 5, 2, 0, 1, 4, 9, 2, 5, 0, 1];

        $legalBallIdx = 38;
        $currentOver = 7;

        for ($over = $currentOver; $over < 20; $over++) {
            $overEvents = [];
            $currentBowlerRef = $bowlerRotation[$over % count($bowlerRotation)];

            for ($ballInOver = 0; $ballInOver < 6; $ballInOver++) {
                if ($legalBallIdx >= count($allBalls)) break;

                $ballData = $allBalls[$legalBallIdx];
                $legalBallIdx++;

                $isWicket = false;
                for ($b = 0; $b < $legalBallIdx; $b++) {
                    if ($allBalls[$b]['is_four'] || $allBalls[$b]['is_six'] || $allBalls[$b]['runs'] > 0) {
                    }
                }

                $batterExpectedBalls = $battingData[$strikerOrder - 1][2] ?? 999;
                if ($batterExpectedBalls > 0 && $legalBallSequence >= $batterExpectedBalls + 38) {
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
                    while ($nextBatterOrder <= 11 && $battingData[$nextBatterOrder - 1][2] === 0) {
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
            $players = User::whereHas('clubMemberships', fn ($q) => $q->where('club_id', $innings->batting_team_id)->where('role', 'player'))
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