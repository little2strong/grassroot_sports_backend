<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ClubInvitationMail;
use App\Models\AppNotification;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Invitation;
use App\Models\PlayerProfile;
use App\Models\TeamMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function publicClubPlayers(string $club): JsonResponse
    {
        $club = $this->resolveClub($club);

        if (!$club) {
            return response()->json([
                'message' => 'Club not found.',
            ], 404);
        }

        if (!$club->is_public) {
            return response()->json([
                'message' => 'This club is not public.',
            ], 403);
        }

        $members = $club->members()
            ->active()
            ->whereHas('user', fn ($query) => $query
                ->where('user_type', 'player')
                ->where('is_active', true))
            ->with([
                'user.playerProfile',
                'user.teamMemberships' => fn ($query) => $query
                    ->active()
                    ->whereHas('team', fn ($teamQuery) => $teamQuery->where('club_id', $club->id))
                    ->with('team'),
            ])
            ->latest('joined_at')
            ->get();

        return response()->json([
            'message' => 'Club players fetched successfully.',
            'data' => [
                'club' => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'slug' => $club->slug,
                    'short_name' => $club->short_name,
                    'logo_url' => $this->assetUrl($club->logo),
                    'is_verified' => $club->is_verified,
                ],
                'players_count' => $members->count(),
                'players' => $members->map(fn ($member) => $this->formatPublicClubPlayer($member, $club))->values(),
            ],
        ]);
    }

    public function player(Request $request): JsonResponse
    {
        $user = $request->user();
        // dd($user->clubMemberships);

        if ($user->user_type !== 'player') {
            return response()->json([
                'message' => 'Only player accounts can access player profile.',
            ], 403);
        }

        $user->load(['playerProfile', 'clubs', 'clubMemberships.club', 'teams']);

        return response()->json([
            'message' => 'Player profile fetched successfully.',
            'data' => $this->formatPlayerProfile($user),
        ]);
    }

    public function updatePlayer(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'player') {
            return response()->json([
                'message' => 'Only player accounts can update player profile.',
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'image' => 'sometimes|nullable|image|max:5048',
            'batting_style' => 'sometimes|nullable|in:right_hand,left_hand',
            'bowling_style' => 'sometimes|nullable|in:right_arm_fast,right_arm_fast_medium,right_arm_medium,right_arm_off_break,right_arm_leg_break,left_arm_fast,left_arm_fast_medium,left_arm_medium,left_arm_orthodox,left_arm_chinaman',
            'primary_role' => 'sometimes|required|in:batsman,bowler,all_rounder,wicket_keeper',
            'bio' => 'sometimes|nullable|string|max:2000',
            'is_public_profile' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storeUpload($request, 'image', 'image');
        }

        $user->fill(collect($validated)->only([
            'first_name',
            'last_name',
            'phone',
            'image',
        ])->all())->save();

        $profileData = collect($validated)->only([
            'batting_style',
            'bowling_style',
            'primary_role',
            'bio',
            'is_public_profile',
        ])->all();

        if ($profileData) {
            PlayerProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        $user->load(['playerProfile', 'clubs', 'teams']);

        return response()->json([
            'message' => 'Player profile updated successfully.',
            'data' => $this->formatPlayerProfile($user),
        ]);
    }

    public function club(Request $request): JsonResponse
    {
        $userid = $request->user;

        $user = User::find($userid);

        if ($user->user_type !== 'club') {
            return response()->json([
                'message' => 'Only club accounts can access club profile.',
            ], 403);
        }

        $club = $user->ownedClub()
            ->with(['owner', 'teams'])
            ->withCount(['members', 'teams', 'followers'])
            ->first();

        if (!$club) {
            return response()->json([
                'message' => 'Club profile not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Club profile fetched successfully.',
            'data' => $this->formatClubProfile($club),
        ]);
    }

    public function updateClub(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'club') {
            return response()->json([
                'message' => 'Only club accounts can update club profile.',
            ], 403);
        }

        $club = $user->ownedClub()->first();

        if (!$club) {
            return response()->json([
                'message' => 'Club profile not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'short_name' => 'sometimes|nullable|string|max:10',
            'country' => 'sometimes|nullable|string|max:100',
            'city' => 'sometimes|nullable|string|max:100',
            'address' => 'sometimes|nullable|string|max:500',
            'website' => 'sometimes|nullable|url|max:255',
            'founded_year' => 'sometimes|nullable|integer|min:1800|max:' . date('Y'),
            'description' => 'sometimes|nullable|string|max:2000',
            'logo' => 'sometimes|nullable|image|max:1024',
            'cover_image' => 'sometimes|nullable|image|max:4096',
            'is_public' => 'sometimes|boolean',
            'show_public_profiles' => 'sometimes|boolean',
            'hide_player_names_publicly' => 'sometimes|boolean',
            'hide_player_photos_publicly' => 'sometimes|boolean',
        ]);
        // dd($validated);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeUpload($request, 'logo', 'logo');
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $this->storeUpload($request, 'cover_image', 'cover');
        }

        $club->update($validated);

        $club = Club::query()
            ->with(['owner', 'teams'])
            ->withCount(['members', 'teams', 'followers'])
            ->findOrFail($club->id);

        return response()->json([
            'message' => 'Club profile updated successfully.',
            'data' => $this->formatClubProfile($club),
        ]);
    }

    public function createSquad(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'club') {
            return response()->json(['message' => 'Only club accounts can create squads.'], 403);
        }

        $club = $user->ownedClub()->first();

        if (!$club) {
            return response()->json(['message' => 'Club profile not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'sometimes|nullable|string|max:10',
            'primary_color' => 'sometimes|nullable|string|max:20',
            'secondary_color' => 'sometimes|nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $teamData = array_merge($validated, ['club_id' => $club->id]);

        $teamData['primary_color'] = $teamData['primary_color'] ?? '#1e3a5f';
        $teamData['secondary_color'] = $teamData['secondary_color'] ?? '#ffffff';

        $team = Team::create($teamData);

        return response()->json([
            'message' => 'Squad created successfully.',
            'data' => [
                'squad' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'short_name' => $team->short_name,
                    'primary_color' => $team->primary_color,
                    'secondary_color' => $team->secondary_color,
                    'is_active' => $team->is_active,
                ],
            ],
        ], 201);
    }

    public function addPlayerToSquad(Request $request, int $teamId): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'club') {
            return response()->json(['message' => 'Only club accounts can add players to squads.'], 403);
        }

        $club = $user->ownedClub()->first();

        if (!$club) {
            return response()->json(['message' => 'Club profile not found.'], 404);
        }

        $team = Team::query()->find($teamId);

        if (!$team || $team->club_id !== $club->id) {
            return response()->json(['message' => 'Squad not found for your club.'], 404);
        }

        $validated = $request->validate([
            'player_id' => [
                'required_without:email',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('user_type', 'player')->where('is_active', true)),
            ],
            'email' => 'required_without:player_id|nullable|email|max:255',
            'role' => 'sometimes|required|in:player,captain,manager,scorer',
            'jersey_number' => 'sometimes|nullable|integer|min:0',
        ]);

        $invitee = isset($validated['player_id'])
            ? User::query()->where('user_type', 'player')->find($validated['player_id'])
            : User::query()->where('email', $validated['email'])->where('user_type', 'player')->where('is_active', true)->first();

        if (!$invitee) {
            return response()->json(['message' => 'Player not found.'], 422);
        }

        $existing = $team->members()->where('user_id', $invitee->id)->first();

        if ($existing && $existing->is_active) {
            return response()->json(['message' => 'Player is already an active member of this squad.'], 422);
        }

        $member = \App\Models\TeamMember::updateOrCreate(
            ['team_id' => $team->id, 'user_id' => $invitee->id],
            [
                'role' => $validated['role'] ?? 'player',
                'jersey_number' => $validated['jersey_number'] ?? null,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Player added to squad.',
            'data' => [
                'member' => [
                    'id' => $member->id,
                    'team_id' => $member->team_id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'jersey_number' => $member->jersey_number,
                    'is_active' => (bool)$member->is_active,
                    'joined_at' => optional($member->joined_at)->toIso8601String(),
                ],
            ],
        ], 201);
    }

    public function invitePlayerToClub(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'club') {
            return response()->json([
                'message' => 'Only club accounts can invite players.',
            ], 403);
        }

        $club = $user->ownedClub()->first();

        if (!$club) {
            return response()->json([
                'message' => 'Club profile not found.',
            ], 404);
        }

        $validated = $request->validate([
            'player_id' => [
                'required_without:email',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('user_type', 'player')
                    ->where('is_active', true)),
            ],
            'email' => 'required_without:player_id|nullable|email|max:255',
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($query) => $query->where('club_id', $club->id)),
            ],
            'role' => 'sometimes|required|in:admin,captain,manager,scorer,player',
            'message' => 'nullable|string|max:1000',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        $invitee = isset($validated['player_id'])
            ? User::query()->where('user_type', 'player')->find($validated['player_id'])
            : User::query()
                ->where('email', $validated['email'])
                ->where('user_type', 'player')
                ->where('is_active', true)
                ->first();
        // dd($invitee, $request->all());

        $email = $invitee?->email ?? $validated['email'];

        $alreadyMember = $invitee && $club->members()
            ->active()
            ->where('user_id', $invitee->id)
            ->exists();

        if ($alreadyMember) {
            return response()->json([
                'message' => 'This player is already an active member of the club.',
            ], 422);
        }

        $pendingInvitation = Invitation::query()
            ->where('club_id', $club->id)
            ->where('invited_email', $email)
            ->pending()
            ->first();

        if ($pendingInvitation) {
            return response()->json([
                'message' => 'A pending invitation already exists for this player.',
                'data' => [
                    'invitation' => $this->formatInvitation($pendingInvitation),
                ],
            ], 422);
        }

        $invitation = Invitation::create([
            'club_id' => $club->id,
            'team_id' => $validated['team_id'] ?? null,
            'invited_by' => $user->id,
            'invited_email' => $email,
            'invited_phone' => $invitee?->phone,
            'invited_user_id' => $invitee?->id,
            'role' => $validated['role'] ?? 'player',
            'status' => 'pending',
            'expires_at' => now()->addDays((int) ($validated['expires_in_days'] ?? 7)),
            'message' => $validated['message'] ?? null,
        ]);

        $invitation->load(['club', 'team', 'invitedBy', 'invitedUser']);

        if ($invitee) {
            AppNotification::create([
                'user_id' => $invitee->id,
                'type' => AppNotification::TYPE_INVITATION_RECEIVED,
                'title' => 'Club invitation received',
                'message' => $club->name . ' invited you to join as ' . $invitation->role_label . '.',
                'notifiable_type' => Invitation::class,
                'notifiable_id' => $invitation->id,
                'data' => [
                    'invitation_id' => $invitation->id,
                    'club_id' => $club->id,
                    'club_name' => $club->name,
                    'team_id' => $invitation->team_id,
                    'role' => $invitation->role,
                    'accept_url' => $invitation->accept_url,
                    'reject_url' => $invitation->reject_url,
                ],
                'sent_email' => true,
            ]);
        }

        Mail::to($email)->send(new ClubInvitationMail($invitation));

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'data' => [
                'invitation' => $this->formatInvitation($invitation),
            ],
        ], 201);
    }

    public function acceptInvitation(string $token): JsonResponse
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->with(['club', 'team', 'invitedUser'])
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found.',
            ], 404);
        }

        if (!$invitation->isPending()) {
            return response()->json([
                'message' => 'This invitation is no longer available.',
                'data' => [
                    'invitation' => $this->formatInvitation($invitation),
                ],
            ], 422);
        }

        $user = $invitation->invitedUser ?: User::query()
            ->where('email', $invitation->invited_email)
            ->where('user_type', 'player')
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Please register as a player with the invited email before accepting this invitation.',
            ], 422);
        }

        ClubMember::updateOrCreate(
            [
                'club_id' => $invitation->club_id,
                'user_id' => $user->id,
            ],
            [
                'role' => $invitation->role,
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        if ($invitation->team_id) {
            TeamMember::updateOrCreate(
                [
                    'team_id' => $invitation->team_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => in_array($invitation->role, ['captain', 'manager', 'scorer'])
                        ? $invitation->role
                        : 'player',
                    'is_active' => true,
                    'joined_at' => now(),
                ]
            );
        }

        $invitation->accept($user->id);
        $invitation->refresh()->load(['club', 'team', 'invitedBy', 'invitedUser']);

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'data' => [
                'invitation' => $this->formatInvitation($invitation),
            ],
        ]);
    }

    public function rejectInvitation(string $token): JsonResponse
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->with(['club', 'team', 'invitedBy', 'invitedUser'])
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found.',
            ], 404);
        }

        if (!$invitation->isPending()) {
            return response()->json([
                'message' => 'This invitation is no longer available.',
                'data' => [
                    'invitation' => $this->formatInvitation($invitation),
                ],
            ], 422);
        }

        $invitation->reject();
        $invitation->refresh()->load(['club', 'team', 'invitedBy', 'invitedUser']);

        return response()->json([
            'message' => 'Invitation rejected successfully.',
            'data' => [
                'invitation' => $this->formatInvitation($invitation),
            ],
        ]);
    }

    private function formatPlayerProfile($user): array
    {
        $profile = $user->playerProfile;


        return [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image,
                'image_url' => $this->assetUrl($user->image),
                'user_type' => $user->user_type,
                'is_active' => $user->is_active,
                'is_onboarded' => $user->is_onboarded,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
            'player_profile' => $profile ? [
                'id' => $profile->id,
                'batting_style' => $profile->batting_style,
                'batting_style_label' => $profile->batting_style_label,
                'bowling_style' => $profile->bowling_style,
                'bowling_style_label' => $profile->bowling_style_label,
                'primary_role' => $profile->primary_role,
                'role_label' => $profile->role_label,
                'bio' => $profile->bio,
                'total_matches' => $profile->total_matches,
                'total_runs' => $profile->total_runs,
                'total_wickets' => $profile->total_wickets,
                'highest_score' => $profile->highest_score,
                'total_fifties' => $profile->total_fifties,
                'total_hundreds' => $profile->total_hundreds,
                'total_five_wickets' => $profile->total_five_wickets,
                'average' => $profile->average,
                'is_public_profile' => $profile->is_public_profile,
            ] : null,
            'clubs' => $user->clubs->isNotEmpty()
                ? $user->clubs->map(fn ($club) => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'slug' => $club->slug,
                    'short_name' => $club->short_name,
                    'logo_url' => $this->assetUrl($club->logo),
                    'role' => $club->pivot->role,
                    'status' => $club->pivot->status,
                    'joined_at' => optional($club->pivot->joined_at)->toIso8601String(),
                ])
                : $user->clubMemberships
                    ->filter(fn ($membership) => $membership->club)
                    ->map(fn ($membership) => [
                        'id' => $membership->club->id,
                        'name' => $membership->club->name,
                        'slug' => $membership->club->slug,
                        'short_name' => $membership->club->short_name,
                        'logo_url' => $this->assetUrl($membership->club->logo),
                        'role' => $membership->role,
                        'status' => $membership->status,
                        'joined_at' => optional($membership->joined_at)->toIso8601String(),
                    ])
                    ->values(),
            'teams' => $user->teams->map(fn ($team) => [
                'id' => $team->id,
                'club_id' => $team->club_id,
                'name' => $team->name,
                'slug' => $team->slug,
                'short_name' => $team->short_name,
                'role' => $team->pivot->role,
                'jersey_number' => $team->pivot->jersey_number,
                'joined_at' => optional($team->pivot->joined_at)->toIso8601String(),
            ])->values(),
        ];
    }

    private function formatClubProfile(Club $club): array
    {
        return [
            'club' => [
                'id' => $club->id,
                'owner_id' => $club->owner_id,
                'name' => $club->name,
                'slug' => $club->slug,
                'short_name' => $club->short_name,
                'logo' => $club->logo,
                'logo_url' => $this->assetUrl($club->logo),
                'cover_image' => $club->cover_image,
                'cover_image_url' => $this->assetUrl($club->cover_image),
                'description' => $club->description,
                'country' => $club->country,
                'city' => $club->city,
                'address' => $club->address,
                'website' => $club->website,
                'founded_year' => $club->founded_year,
                'is_public' => $club->is_public,
                'is_verified' => $club->is_verified,
                'show_public_profiles' => $club->show_public_profiles,
                'hide_player_names_publicly' => $club->hide_player_names_publicly,
                'hide_player_photos_publicly' => $club->hide_player_photos_publicly,
                'members_count' => $club->members_count,
                'teams_count' => $club->teams_count,
                'followers_count' => $club->followers_count,
                'created_at' => optional($club->created_at)->toIso8601String(),
            ],
            'owner' => [
                'id' => $club->owner->id,
                'first_name' => $club->owner->first_name,
                'last_name' => $club->owner->last_name,
                'full_name' => trim($club->owner->first_name . ' ' . $club->owner->last_name),
                'email' => $club->owner->email,
                'phone' => $club->owner->phone,
            ],
            'teams' => $club->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'short_name' => $team->short_name,
                'logo_url' => $this->assetUrl($team->logo),
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'is_active' => $team->is_active,
            ])->values(),
        ];
    }

    private function formatInvitation(Invitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'club_id' => $invitation->club_id,
            'team_id' => $invitation->team_id,
            'invited_by' => $invitation->invited_by,
            'invited_email' => $invitation->invited_email,
            'invited_phone' => $invitation->invited_phone,
            'invited_user_id' => $invitation->invited_user_id,
            'role' => $invitation->role,
            'role_label' => $invitation->role_label,
            'status' => $invitation->status,
            'status_label' => $invitation->status_label,
            'message' => $invitation->message,
            'token' => $invitation->token,
            'accept_url' => $invitation->accept_url,
            'reject_url' => $invitation->reject_url,
            'expires_at' => optional($invitation->expires_at)->toIso8601String(),
            'created_at' => optional($invitation->created_at)->toIso8601String(),
        ];
    }

    private function formatPublicClubPlayer($member, Club $club): array
    {
        $user = $member->user;
        $profile = $user->playerProfile;
        $showProfile = $club->show_public_profiles && (!$profile || $profile->is_public_profile);
        $fullName = trim($user->first_name . ' ' . $user->last_name);

        return [
            'id' => $user->id,
            'first_name' => $club->hide_player_names_publicly ? null : $user->first_name,
            'last_name' => $club->hide_player_names_publicly ? null : $user->last_name,
            'full_name' => $club->hide_player_names_publicly ? 'Player #' . $user->id : $fullName,
            'image_url' => $club->hide_player_photos_publicly ? null : $this->assetUrl($user->image),
            'club_role' => $member->role,
            'joined_at' => optional($member->joined_at)->toIso8601String(),
            'player_profile' => $showProfile && $profile ? [
                'primary_role' => $profile->primary_role,
                'role_label' => $profile->role_label,
                'batting_style' => $profile->batting_style,
                'batting_style_label' => $profile->batting_style_label,
                'bowling_style' => $profile->bowling_style,
                'bowling_style_label' => $profile->bowling_style_label,
                'bio' => $profile->bio,
                'total_matches' => $profile->total_matches,
                'total_runs' => $profile->total_runs,
                'total_wickets' => $profile->total_wickets,
                'highest_score' => $profile->highest_score,
                'total_fifties' => $profile->total_fifties,
                'total_hundreds' => $profile->total_hundreds,
                'total_five_wickets' => $profile->total_five_wickets,
                'average' => $profile->average,
            ] : null,
            'teams' => $user->teamMemberships->map(fn ($teamMember) => [
                'id' => $teamMember->team->id,
                'name' => $teamMember->team->name,
                'slug' => $teamMember->team->slug,
                'short_name' => $teamMember->team->short_name,
                'role' => $teamMember->role,
                'jersey_number' => $teamMember->jersey_number,
            ])->values(),
        ];
    }

    private function resolveClub(string $club): ?Club
    {
        return Club::query()
            ->where('slug', $club)
            ->when(is_numeric($club), fn ($query) => $query->orWhere('id', $club))
            ->first();
    }

    private function storeUpload(Request $request, string $field, string $suffix): string
    {
        $file = $request->file($field);
        $fileName = time() . '_' . $suffix . '.' . $file->getClientOriginalExtension();

        $file->move(public_path('uploads/user'), $fileName);

        return 'uploads/user/' . $fileName;
    }

    private function assetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }
}
