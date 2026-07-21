<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailOtpMail;
use Throwable;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email with OTP before logging in.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        // Load related data based on user type
        if ($user->user_type === 'player') {
            $user->load('playerProfile');
        } elseif ($user->user_type === 'club') {
            $user->load([
                'ownedClub',
                'ownedClub.teams',
            ]);
        }

        // Delete old tokens (optional)
        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'data' => [
                'user' => $user,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        auth('sanctum')->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'message' => 'No account found with this email address.',
                    'errors' => [
                        'email' => ['Email not registered in our system.'],
                    ],
                ], 404);
            }

            $otp = (string) random_int(100000, 999999);
            $expiresInMinutes = 15;

            $user->update([
                'password_reset_token' => Hash::make($otp),
                'password_reset_expires_at' => now()->addMinutes($expiresInMinutes),
            ]);

            Mail::to($user->email)->send(new EmailOtpMail(
                otp: $otp,
                expiresInMinutes: $expiresInMinutes,
                purpose: 'Password Reset'
            ));

            return response()->json([
                'message' => 'Password reset OTP has been sent to your email.',
                'data' => [
                    'email' => $user->email,
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request. Please try again.',
                'errors' => [
                    'email' => ['Failed to send reset OTP.'],
                ],
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|min:4|max:10',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'message' => 'No account found with this email address.',
                    'errors' => [
                        'email' => ['Email not registered in our system.'],
                    ],
                ], 404);
            }

            if (!$user->password_reset_expires_at || $user->password_reset_expires_at->isPast()) {
                return response()->json([
                    'message' => 'OTP expired. Please request a new OTP.',
                    'errors' => [
                        'otp' => ['OTP has expired.'],
                    ],
                ], 422);
            }

            if (!$user->password_reset_token || !Hash::check($validated['otp'], $user->password_reset_token)) {
                return response()->json([
                    'message' => 'Invalid OTP.',
                    'errors' => [
                        'otp' => ['Incorrect OTP.'],
                    ],
                ], 422);
            }

            $user->update([
                'password' => Hash::make($validated['password']),
                'password_reset_token' => null,
                'password_reset_expires_at' => null,
            ]);

            return response()->json([
                'message' => 'Password has been reset successfully.',
                'data' => [
                    'email' => $user->email,
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'An error occurred while resetting your password. Please try again.',
                'errors' => [
                    'password' => ['Failed to reset password.'],
                ],
            ], 500);
        }
    }
}
