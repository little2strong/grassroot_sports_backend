<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PublicMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMatchController extends Controller
{
    public function __construct(private readonly PublicMatchService $publicMatches)
    {
    }

    /**
     * GET /api/public/matches/live
     * List all publicly visible live or paused matches.
     */
    public function liveMatches(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $paginator = $this->publicMatches->listLive($perPage);

        return response()->json([
            'message' => 'Live matches fetched successfully.',
            'data' => collect($paginator->items())->map(fn ($f) => $this->publicMatches->formatFixtureCard($f))->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * GET /api/public/matches/upcoming
     * List upcoming public fixtures.
     */
    public function upcomingMatches(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $paginator = $this->publicMatches->listUpcoming($perPage);

        return response()->json([
            'message' => 'Upcoming matches fetched successfully.',
            'data' => collect($paginator->items())->map(fn ($f) => $this->publicMatches->formatFixtureCard($f))->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * GET /api/public/matches/completed
     * List completed public matches.
     */
    public function completedMatches(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $paginator = $this->publicMatches->listCompleted($perPage);

        return response()->json([
            'message' => 'Completed matches fetched successfully.',
            'data' => collect($paginator->items())->map(fn ($f) => $this->publicMatches->formatFixtureCard($f))->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * GET /api/public/matches/{slug}
     * Match overview by public_share_slug or fixture ID.
     */
    public function show(string $slug): JsonResponse
    {
        $fixture = $this->publicMatches->findPublicFixture($slug);

        if (!$fixture) {
            return response()->json(['message' => 'Match not found or not publicly available.'], 404);
        }

        return response()->json([
            'message' => 'Match fetched successfully.',
            'data' => $this->publicMatches->formatFixtureDetail($fixture),
        ]);
    }

    /**
     * GET /api/public/matches/{slug}/score
     * Live or completed scorecard — no authentication required.
     * Poll this endpoint during live matches (e.g. every 5–10 seconds).
     */
    public function score(string $slug): JsonResponse
    {
        $fixture = $this->publicMatches->findPublicFixture($slug);

        if (!$fixture) {
            return response()->json(['message' => 'Match not found or not publicly available.'], 404);
        }

        return response()->json([
            'message' => 'Score fetched successfully.',
            'data' => $this->publicMatches->getPublicScore($fixture),
        ]);
    }

    /**
     * GET /api/public/fixtures/{fixtureId}/score
     * Same as score by slug, but using fixture ID.
     */
    public function scoreByFixtureId(int $fixtureId): JsonResponse
    {
        $fixture = $this->publicMatches->findPublicFixture($fixtureId);

        if (!$fixture) {
            return response()->json(['message' => 'Match not found or not publicly available.'], 404);
        }

        return response()->json([
            'message' => 'Score fetched successfully.',
            'data' => $this->publicMatches->getPublicScore($fixture),
        ]);
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
