<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    public function index(Request $request)
    {
        // if (is_null($this->user) || !$this->user->can('admin.user.view')) {
        //         abort(403, 'Sorry !! You are Unauthorized.');
        // }
        $data['title'] = 'Manage Clubs';
        $query = Club::withCount(['teams', 'members']);

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('city', 'like', "%{$request->search}%")
                ->orWhere('country', 'like', "%{$request->search}%")
            );
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        $clubs = $query->orderBy($sortBy, $sortDir)->paginate($request->get('per_page', 15));

        $countries = Club::whereNotNull('country')
            ->distinct()->pluck('country')->sort()->values();

        return view('admin.club.index', compact('clubs', 'countries'));        
    }
}
