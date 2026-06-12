<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function registerStep1(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string|max:20|unique:users,phone',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        return response()->json([
            'message' => 'Basic info saved. Proceed to onboarding.',
            'data' => [
                'token' => encrypt(json_encode([
                    'first_name' => $validated['first_name'],
                    'last_name'  => $validated['last_name'],
                    'email'      => $validated['email'],
                    'phone'      => $validated['phone'] ?? null,
                    'password'   => $validated['password'],
                ])),
            ],
            'onboarding' => [
                'steps' => [
                    [
                        'id'    => 'club',
                        'label' => 'Register as Club',
                    ],
                    [
                        'id'    => 'player',
                        'label' => 'Register as Player',
                    ],
                ],
            ],
        ], 201);
    }

    /**
     * STEP 2: Onboarding — Create Club or Player
     *
     * POST /api/auth/register/onboarding
     * Header: X-Auth-Token: <token from step 1>
     * Body:
     *   choice: "club" or "player"
     *
     * If choice = "player":
     *   { batting_style, bowling_style, primary_role, image (all optional) }
     *
     * If choice = "club":
     *   { club_name, club_short_name, country, city, address, website, founded_year,
     *     description, logo, cover_image, is_public }
     */
    public function registerStep2(Request $request): JsonResponse
    {
        // dd($request->headers->all());
        $token = $request->bearerToken();

        if (!$token) {
            throw ValidationException::withMessages([
                'token' => 'Missing auth token. Send your step 1 response token in X-Auth-Token header.',
            ]);
        }

        $decoded = json_decode(decrypt($token), true);

        if (!$decoded) {
            throw ValidationException::withMessages([
                'token' => 'Invalid or expired token.',
            ]);
        }

        $choice = $request->input('choice');

        if (!in_array($choice, ['club', 'player'])) {
            throw ValidationException::withMessages([
                'choice' => 'Choice must be "club" or "player".',
            ]);
        }

        return match ($choice) {
            'player' => $this->registerAsPlayer($decoded, $request),
            'club'   => $this->registerAsClub($decoded, $request),
        };
    }

    /**
     * Create Player
     */
    private function registerAsPlayer(array $decoded, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batting_style' => 'nullable|in:right_hand,left_hand',
            'bowling_style' => 'nullable|in:right_arm_fast,right_arm_fast_medium,right_arm_medium,right_arm_off_break,right_arm_leg_break,left_arm_fast,left_arm_fast_medium,left_arm_medium,left_arm_orthodox,left_arm_chinaman',
            'primary_role' => 'nullable|in:batsman,bowler,all_rounder,wicket_keeper',
            'image' => 'nullable|image|max:5048',
        ]);

        // Upload image before transaction
        $image = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $imageName = time() . '_image.' . $file->getClientOriginalExtension();

            $file->move(public_path('uploads/user'), $imageName);

            $image = 'uploads/user/' . $imageName;
        }

        return DB::transaction(function () use ($decoded, $validated, $image) {

            $user = User::create([
                'first_name' => $decoded['first_name'],
                'last_name' => $decoded['last_name'],
                'name' => trim($decoded['first_name'] . ' ' . $decoded['last_name']),
                'email' => $decoded['email'],
                'phone' => $decoded['phone'],
                'password' => Hash::make($decoded['password']),
                'image' => $image,
                'user_type' => 'player',
                'is_active' => true,
                'email_verified_at' => now(),
                'is_onboarded' => '1'
            ]);

            $user->playerProfile()->create([
                'batting_style' => $validated['batting_style'] ?? null,
                'bowling_style' => $validated['bowling_style'] ?? null,
                'primary_role' => $validated['primary_role'] ?? 'all_rounder',
            ]);

            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'message' => 'Registration successful! Welcome aboard.',
                'data' => $this->formatUserResponse($user, $token),
            ], 201);
        });
    }

    /**
     * Create Club
     */
    private function registerAsClub(array $decoded, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'club_name' => 'required|string|max:255',
            'club_short_name' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|max:1024',
            'cover_image' => 'nullable|image|max:4096',
            'is_public' => 'boolean',
        ]);

        // Upload logo
        $logo = null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            $logoName = time() . '_logo.' . $file->getClientOriginalExtension();

            $file->move(public_path('uploads/user'), $logoName);

            $logo = 'uploads/user/' . $logoName;
        }

        // Upload cover image
        $coverImage = null;

        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');

            $coverName = time() . '_cover.' . $file->getClientOriginalExtension();

            $file->move(public_path('uploads/user'), $coverName);

            $coverImage = 'uploads/user/' . $coverName;
        }

        return DB::transaction(function () use ($decoded, $validated, $logo, $coverImage) {

            $user = User::create([
                'first_name' => $decoded['first_name'],
                'last_name' => $decoded['last_name'],
                'name' => trim($decoded['first_name'] . ' ' . $decoded['last_name']),
                'email' => $decoded['email'],
                'phone' => $decoded['phone'],
                'password' => Hash::make($decoded['password']),
                'user_type' => 'club',
                'is_active' => true,
                'email_verified_at' => now(),
                'is_onboarded' => '1',
            ]);

            $slug = \Illuminate\Support\Str::slug($validated['club_name']) . '-' . \Illuminate\Support\Str::random(6);

            $club = Club::create([
                'owner_id' => $user->id,
                'name' => $validated['club_name'],
                'slug' => $slug,
                'short_name' => $validated['club_short_name'],
                'logo' => $logo,
                'cover_image' => $coverImage,
                'country' => $validated['country'] ?? null,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'] ?? null,
                'website' => $validated['website'] ?? null,
                'founded_year' => $validated['founded_year'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_public' => $validated['is_public'] ?? true,
                'is_verified' => false,
                'hide_player_names_publicly' => false,
                'hide_player_photos_publicly' => false,
                'show_public_profiles' => true,
            ]);

            ClubMember::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            Team::create([
                'club_id' => $club->id,
                'name' => $validated['club_short_name'] ?? $validated['club_name'],
                'slug' => \Illuminate\Support\Str::slug(
                    $validated['club_short_name'] ?? $validated['club_name']
                ),
                'short_name' => $validated['club_short_name'],
                'is_active' => true,
            ]);

            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'message' => 'Club created successfully! Welcome aboard.',
                'data' => $this->formatClubResponse($user, $club, $token),
            ], 201);
        });
    }

    // ─── Response Formatters ───

    private function formatUserResponse(User $user, string $token): array
    {
        return [
            'user' => [
                'id'             => $user->id,
                'first_name'     => $user->first_name,
                'last_name'      => $user->last_name,
                'full_name'      => $user->full_name,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'image_url'      => $user->image_url,
                'user_type'      => $user->user_type,
                'is_active'      => $user->is_active,
                'roles'          => $user->getRoleNames(),
                'created_at'     => $user->created_at->toIso8601String(),
                'player_profile' => $user->playerProfile,
            ],
            'token' => $token,
        ];
    }

    private function formatClubResponse(User $user, Club $club, string $token): array
    {
        return [
            'user' => [
                'id'            => $user->id,
                'first_name'     => $user->first_name,
                'last_name'      => $user->last_name,
                'full_name'     => $user->full_name,
                'email'         => $user->email,
                'image_url'     => $user->image_url,
                'user_type'     => $user->user_type,
                'is_active'     => $user->is_active,
                'roles'        => $user->getRoleNames(),
                'created_at'    => $user->created_at->toIso8601String(),
            ],
            'club' => [
                'id'            => $club->id,
                'name'          => $club->name,
                'slug'          => $club->slug,
                'short_name'    => $club->short_name,
                'logo_url'      => $club->logo ? asset($club->logo) : null,
                'city'          => $club->city,
                'country'       => $club->country,
                'is_verified'   => $club->is_verified,
                'created_at'    => $club->created_at->toIso8601String(),
            ],
            'token' => $token,
        ];
    }
}
