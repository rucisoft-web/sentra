<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $user = $token ? User::where('api_token', hash('sha256', $token))->where('is_active', true)->first() : null;
        if (! $user) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        }
        $request->attributes->set('service_user', $user);
        AuditLogger::log('api.access', null, $request->method().' '.$request->path(), [], $user->id);
        return $next($request);
    }
}
