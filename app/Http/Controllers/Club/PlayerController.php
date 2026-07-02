<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    use ResolvesClub;

    public function index(Request $request)
    {
        $club = $this->resolveClub($request);

        $members = $club->members()
            ->with('user.playerProfile')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status), fn ($q) => $q->active())
            ->orderByDesc('joined_at')
            ->paginate(15)
            ->withQueryString();

        return view('club.players.index', [
            'title' => 'Players',
            'club' => $club,
            'members' => $members,
            'statusFilter' => $request->query('status'),
        ]);
    }
}
