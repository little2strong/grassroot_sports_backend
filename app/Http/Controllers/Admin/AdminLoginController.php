<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    protected string $redirectTo = '/admin/dashboard';

    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect($this->redirectTo);
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return back()->withErrors([
                'email' => 'No account found with this email.',
            ]);
        }

        // Check status separately
        if (!$admin->isActive()) {
            return back()->withErrors([
                'email' => 'Your account is inactive. Contact support.',
            ]);
        }

        // Check email/password
        if (!Auth::guard('admin')->attempt([
            'email' => $request->email,
            'password' => $request->password,
        ], $request->boolean('remember'))) {

            return back()->withErrors([
                'password' => 'Incorrect password.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin/dashboard');
    }

    public function showForgetForm()
    {
        return view('admin.auth.forget');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if ($admin) {
            $token = Str::random(60);

            \DB::table('admin_password_resets')->updateOrInsert(
                ['email' => $admin->email],
                [
                    'email'      => $admin->email,
                    'token'     => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Send email with $token here when mail is configured
            // Mail::to($admin->email)->send(new AdminPasswordReset($admin, $token));
        }

        // Always show success to prevent email enumeration
        return back()->with('success', 'If an account exists, a reset link has been sent.');
    }

    public function showResetForm($token)
    {
        return view('admin.auth.reset', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = \DB::table('admin_password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid or expired reset token.',
            ]);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'email' => 'No account found.',
            ]);
        }

        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        \DB::table('admin_password_resets')->where('email', $admin->email)->delete();

        return redirect()->route('admin.login')->with('success', 'Password reset successfully. Please login.');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
