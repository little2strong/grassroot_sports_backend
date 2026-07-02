<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClubLoginController extends Controller
{
    protected string $redirectTo = '/club/dashboard';

    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->user_type === 'club') {
            return redirect($this->redirectTo);
        }

        return view('club.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email.'])->onlyInput('email');
        }

        if ($user->user_type !== 'club') {
            return back()->withErrors(['email' => 'This login is for club accounts only.'])->onlyInput('email');
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Your account is inactive. Contact support.'])->onlyInput('email');
        }

        if (!$user->ownedClub()->exists()) {
            return back()->withErrors(['email' => 'No club is linked to this account yet.'])->onlyInput('email');
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.'])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->redirectTo);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('club.login');
    }
}
