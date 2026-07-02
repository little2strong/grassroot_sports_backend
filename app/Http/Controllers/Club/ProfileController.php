<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use ResolvesClub;

    public function index(Request $request): View
    {
        $club = $this->resolveClub($request)
            ->loadCount(['teams', 'members', 'fixtures']);

        return view('club.profile.index', [
            'title' => 'My Club',
            'club' => $club,
        ]);
    }

    public function edit(Request $request): View
    {
        $club = $this->resolveClub($request);

        return view('club.profile.edit', [
            'title' => 'Edit Club',
            'club' => $club,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $club = $this->resolveClub($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|max:1024',
            'cover_image' => 'nullable|image|max:4096',
            'is_public' => 'sometimes|boolean',
            'show_public_profiles' => 'sometimes|boolean',
            'hide_player_names_publicly' => 'sometimes|boolean',
            'hide_player_photos_publicly' => 'sometimes|boolean',
        ]);

        $validated['is_public'] = $request->boolean('is_public');
        $validated['show_public_profiles'] = $request->boolean('show_public_profiles');
        $validated['hide_player_names_publicly'] = $request->boolean('hide_player_names_publicly');
        $validated['hide_player_photos_publicly'] = $request->boolean('hide_player_photos_publicly');

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeClubImage($request->file('logo'), 'logo');
        } else {
            unset($validated['logo']);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $this->storeClubImage($request->file('cover_image'), 'cover');
        } else {
            unset($validated['cover_image']);
        }

        $club->update($validated);

        return redirect()
            ->route('club.profile.index')
            ->with('success', 'Club profile updated successfully.');
    }

    private function storeClubImage($file, string $suffix): string
    {
        $dir = public_path('uploads/clubs');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = time() . '_' . $suffix . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $fileName);

        return 'uploads/clubs/' . $fileName;
    }
}
