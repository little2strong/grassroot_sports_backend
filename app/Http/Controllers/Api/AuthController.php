<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
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

    public function logout(Request $request): JsonResponse
    {
        auth('sanctum')->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
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

            $token = Str::random(64);

            $user->update([
                'password_reset_token' => hash('sha256', $token),
                'password_reset_expires_at' => now()->addHours(1),
            ]);

            Mail::to($user->email)->send(new PasswordResetMail(
                resetUrl: url('/reset-password?token=' . $token . '&email=' . $user->email),
                expiresIn: 60
            ));

            return response()->json([
                'message' => 'Password reset link has been sent to your email.',
                'data' => [
                    'email' => $user->email,
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request. Please try again.',
                'errors' => [
                    'email' => ['Failed to send reset email.'],
                ],
            ], 500);
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = User::where('email', $validated['email'])->first();

            if (!$user || !hash_equals(hash('sha256', $validated['token']), $user->password_reset_token)) {
                return response()->json([
                    'message' => 'Invalid or expired token.',
                ], 422);
            }

            if ($user->password_reset_expires_at && $user->password_reset_expires_at->isPast()) {
                return response()->json([
                    'message' => 'Token has expired.',
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
            ]);
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
