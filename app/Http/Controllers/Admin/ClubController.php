<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Manage Clubs';

        $query = Club::with('owner')
            ->withCount(['teams', 'members', 'fixtures']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                        $ownerQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->filled('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'name', 'city', 'teams_count', 'members_count', 'fixtures_count'];

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $clubs = $query->orderBy($sortBy, $sortDir)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $countries = Club::whereNotNull('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        $summary = [
            'total' => Club::count(),
            'verified' => Club::where('is_verified', true)->count(),
            'public' => Club::where('is_public', true)->count(),
        ];

        return view('admin.club.index', compact('clubs', 'countries', 'title', 'summary'));
    }

    public function show(Club $club)
    {
        $title = $club->name;

        $club->load([
            'owner',
            'teams' => fn ($q) => $q->withCount('members'),
            'members' => fn ($q) => $q->active()->with(['user.playerProfile']),
        ]);

        $fixtures = $club->fixtures()
            ->with(['homeTeam', 'awayTeam', 'venue', 'scorer'])
            ->latest('scheduled_date')
            ->limit(20)
            ->get();

        $stats = [
            'teams' => $club->teams->count(),
            'members' => $club->members->count(),
            'fixtures' => $club->fixtures()->count(),
            'fixtures_live' => $club->fixtures()->whereIn('status', ['live', 'paused'])->count(),
            'fixtures_upcoming' => $club->fixtures()
                ->whereIn('status', ['published', 'draft'])
                ->where('scheduled_date', '>=', now()->toDateString())
                ->count(),
        ];

        return view('admin.club.show', compact('club', 'title', 'fixtures', 'stats'));
    }

    public function toggleVerified(Club $club): RedirectResponse
    {
        $club->update(['is_verified' => !$club->is_verified]);

        return back()->with('success', $club->is_verified
            ? "{$club->name} has been verified."
            : "{$club->name} verification has been removed.");
    }
}
