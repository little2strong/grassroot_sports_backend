<?php

namespace App\Services;

use App\Models\BallEvent;
use App\Models\BattingScore;
use App\Models\BowlingFigure;
use App\Models\Fixture;
use App\Models\Innings;
use App\Models\Matchs;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PublicMatchService
{
    public function __construct(private readonly ScoringService $scoring)
    {
    }

    public function listLive(int $perPage = 20): LengthAwarePaginator
    {
        return $this->publicFixtureQuery()
            ->whereIn('status', ['live', 'paused'])
            ->with($this->listRelations())
            ->orderByDesc('started_at')
            ->paginate($perPage);
    }

    public function listUpcoming(int $perPage = 20): LengthAwarePaginator
    {
        return $this->publicFixtureQuery()
            ->where('status', 'published')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->with($this->listRelations())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate($perPage);
    }

    public function listCompleted(int $perPage = 20): LengthAwarePaginator
    {
        return $this->publicFixtureQuery()
            ->where('status', 'completed')
            ->with($this->listRelations())
            ->orderByDesc('completed_at')
            ->paginate($perPage);
    }

    public function findPublicFixture(string|int $identifier): ?Fixture
    {
        $query = $this->publicFixtureQuery()
            ->with([
                'club',
                'homeTeam',
                'awayTeam',
                'venue',
                'winner',
                'manOfTheMatch',
                'match.firstInnings.battingScores.player',
                'match.firstInnings.bowlingFigures.bowler',
                'match.secondInnings.battingScores.player',
                'match.secondInnings.bowlingFigures.bowler',
                'summary',
            ]);

        if (is_numeric($identifier)) {
            return $query->where('id', (int) $identifier)->first();
        }

        return $query->where('public_share_slug', $identifier)->first();
    }

    public function formatFixtureCard(Fixture $fixture): array
    {
        return [
            'id' => $fixture->id,
            'public_share_slug' => $fixture->public_share_slug,
            'public_url' => $fixture->public_url,
            'status' => $fixture->status,
            'status_label' => $fixture->status_label,
            'match_type' => $fixture->match_type,
            'match_type_label' => $fixture->match_type_label,
            'scheduled_date' => $fixture->scheduled_date?->toDateString(),
            'scheduled_time' => $fixture->scheduled_time
                ? \Carbon\Carbon::parse($fixture->scheduled_time)->format('H:i')
                : null,
            'overs_per_innings' => $fixture->overs_per_innings,
            'home' => $this->formatSideSummary($fixture, 'home'),
            'away' => $this->formatSideSummary($fixture, 'away'),
            'venue' => $fixture->venue ? [
                'id' => $fixture->venue->id,
                'name' => $fixture->venue->name,
                'city' => $fixture->venue->city,
            ] : null,
            'club' => $fixture->club ? [
                'id' => $fixture->club->id,
                'name' => $fixture->club->name,
                'slug' => $fixture->club->slug,
                'logo_url' => $fixture->club->logo_url,
            ] : null,
            'is_live' => $fixture->isLive(),
            'is_completed' => $fixture->isCompleted(),
            'result_text' => $fixture->result_text,
            'started_at' => $fixture->started_at?->toIso8601String(),
            'completed_at' => $fixture->completed_at?->toIso8601String(),
            'match_id' => $fixture->match?->id,
        ];
    }

    public function formatFixtureDetail(Fixture $fixture): array
    {
        $card = $this->formatFixtureCard($fixture);

        return array_merge($card, [
            'toss' => [
                'winner_side' => $fixture->toss_winner_side,
                'decision' => $fixture->toss_decision,
            ],
            'result' => [
                'type' => $fixture->result_type,
                'margin' => $fixture->result_margin,
                'description' => $fixture->result_description,
                'winner_team_id' => $fixture->winner_team_id,
            ],
            'man_of_the_match' => $this->formatMotm($fixture),
            'summary' => $fixture->summary && $fixture->summary->is_published
                ? [
                    'narrative' => $fixture->summary->narrative,
                    'key_stats' => $fixture->summary->key_stats,
                ]
                : null,
            'score' => $this->getPublicScore($fixture),
        ]);
    }

    public function getPublicScore(Fixture $fixture): array
    {
        $match = $fixture->match;

        if (!$match) {
            return [
                'state' => 'not_started',
                'message' => 'Match has not started yet.',
                'fixture_scores' => [
                    'home' => $this->formatSideScore($fixture, 'home'),
                    'away' => $this->formatSideScore($fixture, 'away'),
                ],
            ];
        }

        if (in_array($fixture->status, ['live', 'paused'], true)) {
            return array_merge(
                ['state' => $fixture->status === 'paused' ? 'paused' : 'live'],
                $this->formatPublicLiveState($match->fresh(), $fixture)
            );
        }

        return array_merge(
            ['state' => 'completed'],
            $this->formatCompletedScore($match, $fixture)
        );
    }

    public function formatPublicLiveState(Matchs $match, ?Fixture $fixture = null): array
    {
        $fixture ??= $match->fixture;
        $live = $this->scoring->getLiveState($match);

        return [
            'match' => $live['match'],
            'fixture' => array_merge($live['fixture'], [
                'public_share_slug' => $fixture->public_share_slug,
                'public_url' => $fixture->public_url,
                'home' => $this->formatSideSummary($fixture, 'home'),
                'away' => $this->formatSideSummary($fixture, 'away'),
                'home_score' => $this->formatSideScore($fixture, 'home'),
                'away_score' => $this->formatSideScore($fixture, 'away'),
            ]),
            'current_innings' => $live['innings']
                ? $this->formatPublicInnings($fixture, $live['innings'], true)
                : null,
            'first_innings' => $live['first_innings']
                ? $this->formatPublicInnings($fixture, $live['first_innings'], false)
                : null,
            'second_innings' => $live['second_innings']
                ? $this->formatPublicInnings($fixture, $live['second_innings'], false)
                : null,
            'recent_balls' => collect($live['recent_balls'] ?? [])
                ->map(fn ($ball) => $this->formatPublicBall($ball))
                ->values()
                ->all(),
            'last_updated' => now()->toIso8601String(),
        ];
    }

    private function formatCompletedScore(Matchs $match, Fixture $fixture): array
    {
        $first = $match->firstInnings;
        $second = $match->secondInnings;

        return [
            'match' => [
                'id' => $match->id,
                'fixture_id' => $match->fixture_id,
                'elapsed_time' => $match->elapsed_time,
            ],
            'fixture' => [
                'id' => $fixture->id,
                'status' => $fixture->status,
                'home_display_name' => $fixture->home_display_name,
                'away_display_name' => $fixture->away_display_name,
                'home' => $this->formatSideSummary($fixture, 'home'),
                'away' => $this->formatSideSummary($fixture, 'away'),
                'home_score' => $this->formatSideScore($fixture, 'home'),
                'away_score' => $this->formatSideScore($fixture, 'away'),
                'result_text' => $fixture->result_text,
            ],
            'first_innings' => $first ? $this->formatPublicInningsFromModel($fixture, $first) : null,
            'second_innings' => $second ? $this->formatPublicInningsFromModel($fixture, $second) : null,
            'last_updated' => $fixture->completed_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    private function formatPublicInnings(Fixture $fixture, array $innings, bool $includeCurrentPlayers): array
    {
        $battingScores = collect($innings['batting_scores'] ?? [])
            ->map(fn ($score) => $this->formatPublicBattingScore($fixture, $score, (bool) $innings['batting_is_club']))
            ->values()
            ->all();

        $bowlingFigures = collect($innings['bowling_figures'] ?? [])
            ->map(fn ($figure) => $this->formatPublicBowlingFigure($fixture, $figure, !(bool) $innings['batting_is_club']))
            ->values()
            ->all();

        $data = [
            'id' => $innings['id'],
            'innings_number' => $innings['innings_number'],
            'batting_side' => $innings['batting_is_club'] ? 'club' : 'opponent',
            'runs' => $innings['runs'],
            'wickets' => $innings['wickets'],
            'overs' => $innings['overs'],
            'score_display' => $innings['score_display'],
            'target' => $innings['target'],
            'run_rate' => $innings['run_rate'],
            'required_run_rate' => $innings['required_run_rate'],
            'result' => $innings['result'],
            'batting' => $battingScores,
            'bowling' => $bowlingFigures,
        ];

        if ($includeCurrentPlayers) {
            $data['current_players'] = [
                'striker' => $this->resolveCurrentPlayerName(
                    $fixture,
                    (bool) $innings['batting_is_club'],
                    $innings['striker_id'] ?? null,
                    $innings['external_striker_index'] ?? null
                ),
                'non_striker' => $this->resolveCurrentPlayerName(
                    $fixture,
                    (bool) $innings['batting_is_club'],
                    $innings['non_striker_id'] ?? null,
                    $innings['external_non_striker_index'] ?? null
                ),
                'bowler' => $this->resolveCurrentPlayerName(
                    $fixture,
                    !(bool) $innings['batting_is_club'],
                    $innings['current_bowler_id'] ?? null,
                    $innings['external_bowler_index'] ?? null
                ),
            ];
        }

        return $data;
    }

    private function formatPublicInningsFromModel(Fixture $fixture, Innings $innings): array
    {
        $innings->loadMissing(['battingScores.player', 'bowlingFigures.bowler']);

        return $this->formatPublicInnings($fixture, [
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
            'batting_scores' => $innings->battingScores,
            'bowling_figures' => $innings->bowlingFigures,
        ], false);
    }

    private function formatPublicBattingScore(Fixture $fixture, BattingScore|array $score, bool $isClubBatting): array
    {
        if ($score instanceof BattingScore) {
            return [
                'name' => $this->formatPlayerName(
                    $fixture,
                    $isClubBatting,
                    $score->player,
                    $score->external_player_name,
                    $score->user_id,
                    $score->external_player_index
                ),
                'runs' => $score->runs,
                'balls_faced' => $score->balls_faced,
                'fours' => $score->fours,
                'sixes' => $score->sixes,
                'strike_rate' => $score->strike_rate,
                'is_on_strike' => $score->is_on_strike,
                'is_out' => $score->is_out,
                'dismissal' => $score->is_out ? $score->dismissal_text : ($score->has_batted ? 'not out' : '—'),
                'score_display' => $score->score_display,
            ];
        }

        $player = isset($score['user_id']) ? User::find($score['user_id']) : null;

        return [
            'name' => $this->formatPlayerName(
                $fixture,
                $isClubBatting,
                $player,
                $score['external_player_name'] ?? null,
                $score['user_id'] ?? null,
                $score['external_player_index'] ?? null
            ),
            'runs' => $score['runs'] ?? 0,
            'balls_faced' => $score['balls_faced'] ?? 0,
            'fours' => $score['fours'] ?? 0,
            'sixes' => $score['sixes'] ?? 0,
            'strike_rate' => $score['strike_rate'] ?? null,
            'is_on_strike' => (bool) ($score['is_on_strike'] ?? false),
            'is_out' => (bool) ($score['is_out'] ?? false),
            'dismissal' => ($score['is_out'] ?? false) ? ($score['dismissal_description'] ?? 'out') : 'not out',
            'score_display' => ($score['runs'] ?? 0) . ' (' . ($score['balls_faced'] ?? 0) . ')',
        ];
    }

    private function formatPublicBowlingFigure(Fixture $fixture, BowlingFigure|array $figure, bool $isClubBowling): array
    {
        if ($figure instanceof BowlingFigure) {
            return [
                'name' => $this->formatPlayerName(
                    $fixture,
                    $isClubBowling,
                    $figure->bowler,
                    $figure->external_player_name,
                    $figure->user_id,
                    $figure->external_player_index
                ),
                'overs' => $figure->overs,
                'runs_conceded' => $figure->runs_conceded,
                'wickets' => $figure->wickets,
                'figures_display' => $figure->figures_display,
                'is_current_bowler' => (bool) $figure->is_current_bowler,
            ];
        }

        $bowler = isset($figure['user_id']) ? User::find($figure['user_id']) : null;

        return [
            'name' => $this->formatPlayerName(
                $fixture,
                $isClubBowling,
                $bowler,
                $figure['external_player_name'] ?? null,
                $figure['user_id'] ?? null,
                $figure['external_player_index'] ?? null
            ),
            'overs' => $figure['overs'] ?? 0,
            'runs_conceded' => $figure['runs_conceded'] ?? 0,
            'wickets' => $figure['wickets'] ?? 0,
            'figures_display' => ($figure['wickets'] ?? 0) . '-' . ($figure['runs_conceded'] ?? 0),
            'is_current_bowler' => (bool) ($figure['is_current_bowler'] ?? false),
        ];
    }

    private function formatPublicBall(BallEvent|array $ball): array
    {
        if ($ball instanceof BallEvent) {
            return [
                'over_number' => $ball->over_number,
                'ball_number' => $ball->ball_number,
                'display' => $ball->display_text,
                'color' => $ball->display_color,
                'total_runs' => $ball->total_runs,
                'event_type' => $ball->event_type,
                'is_wicket' => $ball->is_wicket_ball,
                'is_four' => $ball->is_boundary_four,
                'is_six' => $ball->is_boundary_six,
                'commentary' => $ball->commentary,
            ];
        }

        return [
            'over_number' => $ball['over_number'] ?? null,
            'ball_number' => $ball['ball_number'] ?? null,
            'display' => $ball['display_text'] ?? null,
            'total_runs' => $ball['total_runs'] ?? 0,
            'event_type' => $ball['event_type'] ?? null,
            'is_wicket' => (bool) ($ball['is_wicket_ball'] ?? false),
            'commentary' => $ball['commentary'] ?? null,
        ];
    }

    private function formatSideSummary(Fixture $fixture, string $side): array
    {
        $isHome = $side === 'home';

        return [
            'display_name' => $isHome ? $fixture->home_display_name : $fixture->away_display_name,
            'team_id' => $isHome ? $fixture->home_team_id : $fixture->away_team_id,
            'is_external' => $isHome ? $fixture->isHomeExternal() : $fixture->isAwayExternal(),
        ];
    }

    private function formatSideScore(Fixture $fixture, string $side): array
    {
        $isHome = $side === 'home';

        return [
            'runs' => $isHome ? $fixture->home_team_runs : $fixture->away_team_runs,
            'wickets' => $isHome ? $fixture->home_team_wickets : $fixture->away_team_wickets,
            'overs' => $isHome ? $fixture->home_team_overs : $fixture->away_team_overs,
            'display' => $isHome ? $fixture->home_score_display : $fixture->away_score_display,
        ];
    }

    private function formatMotm(Fixture $fixture): ?array
    {
        if (!$fixture->man_of_the_match_id || !$fixture->manOfTheMatch) {
            return null;
        }

        return [
            'name' => $this->formatPlayerName(
                $fixture,
                true,
                $fixture->manOfTheMatch,
                null,
                $fixture->man_of_the_match_id,
                null
            ),
        ];
    }

    private function resolveCurrentPlayerName(
        Fixture $fixture,
        bool $isClubPlayer,
        ?int $userId,
        ?int $externalIndex
    ): ?string {
        if ($userId) {
            return $this->formatPlayerName($fixture, $isClubPlayer, User::find($userId), null, $userId, null);
        }

        if ($externalIndex !== null) {
            $players = array_values($fixture->opponentPlayers() ?? []);

            return $this->formatPlayerName(
                $fixture,
                false,
                null,
                $players[$externalIndex]['name'] ?? null,
                null,
                $externalIndex
            );
        }

        return null;
    }

    private function formatPlayerName(
        Fixture $fixture,
        bool $isClubPlayer,
        ?User $user,
        ?string $externalName,
        ?int $userId,
        ?int $externalIndex
    ): string {
        if (!$isClubPlayer) {
            if ($externalName) {
                return $externalName;
            }

            return $externalIndex !== null ? 'Player ' . ($externalIndex + 1) : 'Opponent Player';
        }

        if ($fixture->club?->hide_player_names_publicly) {
            return $userId ? 'Player #' . $userId : 'Club Player';
        }

        $fullName = trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? ''));

        return $fullName ?: ($user?->email ?? ($userId ? 'Player #' . $userId : 'Club Player'));
    }

    private function publicFixtureQuery(): Builder
    {
        return Fixture::query()
            ->where('is_public', true)
            ->where('status', '!=', 'draft');
    }

    private function listRelations(): array
    {
        return ['club', 'homeTeam', 'awayTeam', 'venue', 'match'];
    }
}
