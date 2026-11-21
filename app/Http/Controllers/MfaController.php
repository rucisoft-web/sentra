<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\Totp;
use Illuminate\Http\Request;

class MfaController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if (! $user->mfa_secret) {
            $user->update(['mfa_secret' => Totp::generateSecret()]);
        }
        $demoCode = config('archive.demo_mode') ? Totp::code($user->mfa_secret) : null;
        return view('auth.mfa', ['user' => $user, 'demoCode' => $demoCode, 'uri' => Totp::uri($user->mfa_secret, $user->email)]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $user = $request->user();
        if (! Totp::verify($user->mfa_secret, $request->code)) {
            AuditLogger::log('mfa_failed', $user, 'Kode MFA salah');
            return back()->withErrors(['code' => 'Kode MFA tidak valid atau kedaluwarsa.']);
        }
        $request->session()->put('mfa_verified_at', now()->timestamp);
        AuditLogger::log('mfa_verified', $user, 'Verifikasi MFA berhasil');
        return redirect($request->session()->pull('mfa_intended', route('dashboard')))->with('success', 'MFA terverifikasi selama '.config('archive.mfa_ttl_minutes').' menit.');
    }
}
