<x-mail::message>
# Password Reset

Click the button below to reset your password:

<x-mail::panel>
<a href="{{ $resetUrl }}" style="display:inline-block;padding:12px 24px;background-color:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">
    Reset Password
</a>
</x-mail::panel>

This link will expire in {{ $expiresIn }} minutes.

If you didn’t request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>