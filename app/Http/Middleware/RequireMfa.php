<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Step-up MFA: wajib untuk approver, admin, ekspor, pemusnahan, dan dokumen Sangat Rahasia. */
class RequireMfa
{
    public static function isFresh(Request $request): bool
    {
        $at = $request->session()->get('mfa_verified_at');
        return $at && now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($at)) < config('archive.mfa_ttl_minutes');
    }

    public function handle(Request $request, Closure $next)
    {
        if (! self::isFresh($request)) {
            $request->session()->put('mfa_intended', $request->isMethod('get') ? $request->fullUrl() : url()->previous());
            return redirect()->route('mfa.show')->with('warning', 'Aksi ini memerlukan verifikasi MFA.');
        }
        return $next($request);
    }
}
