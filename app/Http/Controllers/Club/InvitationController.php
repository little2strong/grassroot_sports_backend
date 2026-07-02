<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Club\Concerns\ResolvesClub;
use App\Mail\ClubInvitationMail;
use App\Models\AppNotification;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvitationController extends Controller
{
    use ResolvesClub;

    public function index(Request $request): View
    {
        $club = $this->resolveClub($request);

        $invitations = Invitation::where('club_id', $club->id)
            ->with(['invitedBy', 'team', 'invitedUser'])
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'expired') {
                    $query->where(function ($q) {
                        $q->where('status', 'expired')
                            ->orWhere(function ($q2) {
                                $q2->where('status', 'pending')->where('expires_at', '<=', now());
                            });
                    });
                } else {
                    $query->where('status', $request->status);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('club.invitations.index', [
            'title' => 'Invitations',
            'club' => $club,
            'invitations' => $invitations,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function create(Request $request): View
    {
        $club = $this->resolveClub($request);

        return view('club.invitations.create', [
            'title' => 'Invite Player',
            'club' => $club,
            'teams' => $club->teams()->active()->orderBy('name')->get(),
            'players' => $this->inviteablePlayers($club),
            'invitation' => new Invitation([
                'role' => 'player',
                'expires_at' => now()->addDays(7),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->resolveClub($request);
        $user = $request->user();

        $validated = $request->validate([
            'player_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('user_type', 'player')
                    ->where('is_active', true)),
            ],
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($query) => $query->where('club_id', $club->id)),
            ],
            'role' => 'required|in:admin,captain,manager,scorer,player',
            'message' => 'nullable|string|max:1000',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        $invitee = User::query()
            ->where('user_type', 'player')
            ->where('is_active', true)
            ->find($validated['player_id']);

        if (!$invitee) {
            return back()
                ->withInput()
                ->with('error', 'Selected player was not found.');
        }

        if ($club->members()->active()->where('user_id', $invitee->id)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'This player is already an active member of your club.');
        }

        $pendingInvitation = Invitation::query()
            ->where('club_id', $club->id)
            ->where('invited_email', $invitee->email)
            ->pending()
            ->first();

        if ($pendingInvitation) {
            return back()
                ->withInput()
                ->with('error', 'A pending invitation already exists for this player.');
        }

        $invitation = Invitation::create([
            'club_id' => $club->id,
            'team_id' => $validated['team_id'] ?? null,
            'invited_by' => $user->id,
            'invited_email' => $invitee->email,
            'invited_phone' => $invitee->phone,
            'invited_user_id' => $invitee->id,
            'role' => $validated['role'],
            'status' => 'pending',
            'expires_at' => now()->addDays((int) ($validated['expires_in_days'] ?? 7)),
            'message' => $validated['message'] ?? null,
        ]);

        $invitation->load(['club', 'team', 'invitedBy', 'invitedUser']);

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

        Mail::to($invitee->email)->send(new ClubInvitationMail($invitation));

        return redirect()
            ->route('club.invitations.index')
            ->with('success', 'Invitation sent to ' . $invitee->full_name . '.');
    }

    public function destroy(Request $request, int $invitation): RedirectResponse
    {
        $club = $this->resolveClub($request);

        $record = Invitation::where('club_id', $club->id)
            ->where('id', $invitation)
            ->firstOrFail();

        if (!$record->isPending()) {
            return redirect()
                ->route('club.invitations.index')
                ->with('error', 'Only pending invitations can be cancelled.');
        }

        $record->cancel();

        return redirect()
            ->route('club.invitations.index')
            ->with('success', 'Invitation cancelled.');
    }

    private function inviteablePlayers($club)
    {
        $memberIds = $club->members()->active()->pluck('user_id');

        $pendingUserIds = Invitation::query()
            ->where('club_id', $club->id)
            ->pending()
            ->whereNotNull('invited_user_id')
            ->pluck('invited_user_id');

        return User::query()
            ->with('playerProfile')
            ->where('user_type', 'player')
            ->where('is_active', true)
            ->whereNotIn('id', $memberIds)
            ->whereNotIn('id', $pendingUserIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }
}
