<x-mail::message>
# {{ $purpose }}

Use this OTP to continue:

<x-mail::panel>
<div style="font-size:28px;font-weight:700;letter-spacing:6px;text-align:center;">
{{ $otp }}
</div>
</x-mail::panel>

This code will expire in {{ $expiresInMinutes }} minutes.

If you didn’t request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

