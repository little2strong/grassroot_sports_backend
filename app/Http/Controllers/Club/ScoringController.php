<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Models\Fixture;
use App\Models\Innings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScoringController extends Controller
{
    use ResolvesClub;

    public function index(Request $request)
    {
        $club = $this->resolveClub($request);

        $liveFixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue', 'match'])
            ->whereIn('status', ['live', 'paused'])
            ->orderByDesc('started_at')
            ->get();

        $readyFixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->where('status', 'published')
            ->whereNotNull('scorer_user_id')
            ->whereDoesntHave('match')
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        return view('club.scoring.index', [
            'title' => 'Scoring',
            'club' => $club,
            'liveFixtures' => $liveFixtures,
            'readyFixtures' => $readyFixtures,
        ]);
    }

    public function matches(Request $request): View
    {
        $club = $this->resolveClub($request);

        $fixtures = Fixture::forClub($club->id)
            ->with(['homeTeam', 'awayTeam', 'venue', 'match'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('scheduled_date')
            ->orderByDesc('scheduled_time')
            ->paginate(15)
            ->withQueryString();

        return view('club.scoring.matches', [
            'title' => 'Matches',
            'club' => $club,
            'fixtures' => $fixtures,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function show(Request $request, int $fixture): View
    {
        $club = $this->resolveClub($request);

        $record = Fixture::forClub($club->id)
            ->with([
                'homeTeam',
                'awayTeam',
                'venue',
                'winner',
                'manOfTheMatch',
                'match.firstInnings.battingScores.player',
                'match.firstInnings.bowlingFigures.bowler',
                'match.firstInnings.wickets.ballEvent',
                'match.secondInnings.battingScores.player',
                'match.secondInnings.bowlingFigures.bowler',
                'match.secondInnings.wickets.ballEvent',
                'summary',
            ])
            ->where('id', $fixture)
            ->firstOrFail();

        $match = $record->match;

        $innings = collect();
        if ($match) {
            $innings = Innings::query()
                ->where('match_id', $match->id)
                ->with([
                    'battingTeam',
                    'bowlingTeam',
                    'battingScores.player',
                    'bowlingFigures.bowler',
                    'wickets.bowler',
                    'wickets.fielderOne',
                    'ballEvents',
                    'overSummaries.bowler',
                ])
                ->orderBy('innings_number')
                ->get();
        }

        return view('club.scoring.show', [
            'title' => 'Match Scorecard',
            'club' => $club,
            'fixture' => $record,
            'match' => $match,
            'innings' => $innings,
        ]);
    }

    public function showPublic(Request $request, string $slug): View
    {
        $fixture = Fixture::where('is_public', true)
            ->where('status', '!=', 'draft')
            ->with([
                'homeTeam',
                'awayTeam',
                'venue',
                'winner',
                'manOfTheMatch',
                'match.firstInnings.battingScores.player',
                'match.firstInnings.bowlingFigures.bowler',
                'match.firstInnings.wickets.ballEvent',
                'match.secondInnings.battingScores.player',
                'match.secondInnings.bowlingFigures.bowler',
                'match.secondInnings.wickets.ballEvent',
                'summary',
            ])
            ->where('public_share_slug', $slug)
            ->firstOrFail();

        $match = $fixture->match;

        $innings = collect();
        if ($match) {
            $innings = Innings::query()
                ->where('match_id', $match->id)
                ->with([
                    'battingTeam',
                    'bowlingTeam',
                    'battingScores.player',
                    'bowlingFigures.bowler',
                    'wickets.bowler',
                    'wickets.fielderOne',
                    'ballEvents',
                    'overSummaries.bowler',
                ])
                ->orderBy('innings_number')
                ->get();
        }

        return view('club.scoring.show-public', [
            'title' => 'Live Score',
            'fixture' => $fixture,
            'match' => $match,
            'innings' => $innings,
        ]);
    }
}
