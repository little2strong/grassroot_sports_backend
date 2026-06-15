<x-mail::message>
# You're invited to join {{ $club->name }}

{{ trim($invitedBy->first_name . ' ' . $invitedBy->last_name) }} invited you to join {{ $club->name }} as {{ $invitation->role_label }}.

@if($team)
You have also been invited to the {{ $team->name }} team.
@endif

@if($invitation->message)
Message from the club:

{{ $invitation->message }}
@endif

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

<x-mail::button :url="$rejectUrl" color="error">
Reject Invitation
</x-mail::button>

This invitation expires on {{ $invitation->expires_at->format('M d, Y h:i A') }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
