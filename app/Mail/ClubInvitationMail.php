<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClubInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation
    ) {
        $this->invitation->loadMissing(['club', 'team', 'invitedBy', 'invitedUser']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation to join ' . $this->invitation->club->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitations.club-player',
            with: [
                'invitation' => $this->invitation,
                'club' => $this->invitation->club,
                'team' => $this->invitation->team,
                'invitedBy' => $this->invitation->invitedBy,
                'acceptUrl' => $this->invitation->accept_url,
                'rejectUrl' => $this->invitation->reject_url,
            ],
        );
    }
}
