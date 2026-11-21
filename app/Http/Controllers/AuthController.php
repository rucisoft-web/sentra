<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        $demoUsers = config('archive.demo_mode')
            ? User::with('department')->where('role', '!=', 'service')->orderBy('id')->get()
            : collect();
        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $cred['email'])->first();

        if (! Auth::attempt($cred + ['is_active' => true], false)) {
            AuditLogger::log('login_failed', $user, 'Login gagal untuk '.$cred['email'], [], $user?->id);
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }
        $user = Auth::user();
        if ($user->isAccessExpired() || $user->role === 'service') {
            Auth::logout();
            return back()->withErrors(['email' => 'Masa berlaku akses akun telah berakhir.']);
        }
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);
        AuditLogger::log('login', $user, 'Login berhasil');
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLogger::log('logout', $request->user(), 'Logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('landing');
    }
}
