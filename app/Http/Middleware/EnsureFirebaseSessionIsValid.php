<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards routes that require a signed-in teacher.
 * Session keys: auth_uid (Supabase, preferred) or firebase_uid (legacy alias).
 */
class EnsureFirebaseSessionIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('auth_uid') && ! session()->has('firebase_uid')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in first.',
            ]);
        }

        return $next($request);
    }
}
