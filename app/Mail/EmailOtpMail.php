<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public int $expiresInMinutes,
        public string $purpose = 'Verify your email'
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose . ' — OTP Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.email-otp',
            with: [
                'otp' => $this->otp,
                'expiresInMinutes' => $this->expiresInMinutes,
                'purpose' => $this->purpose,
            ]
        );
    }
}

