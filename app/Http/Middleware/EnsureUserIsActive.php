<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && (! $u->is_active || $u->isAccessExpired() || $u->role === 'service')) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors(['email' => 'Akun tidak aktif atau masa berlaku akses telah berakhir.']);
        }
        return $next($request);
    }
}
