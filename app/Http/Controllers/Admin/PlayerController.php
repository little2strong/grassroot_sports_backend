<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
   public function index(Request $request)
    {
        $query = User::whereHas('roles', fn ($q) => $q->where('name', 'player'))
            ->with('playerProfile');

        // Search
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
            );
        }

        // Filters
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('primary_role')) {
            $query->whereHas('playerProfile', fn ($q) => $q->where('primary_role', $request->primary_role));
        }

        if ($request->filled('batting_style')) {
            $query->whereHas('playerProfile', fn ($q) => $q->where('batting_style', $request->batting_style));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        $players = $query->orderBy($sortBy, $sortDir)->paginate($request->get('per_page', 15));

        return view('admin.player.index', compact('players'));
              
    }
}
