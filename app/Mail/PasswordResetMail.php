<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
        public int $expiresIn
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset — OTP Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.password-reset',
            with: [
                'resetUrl' => $this->resetUrl,
                'expiresIn' => $this->expiresIn,
            ]
        );
    }
}