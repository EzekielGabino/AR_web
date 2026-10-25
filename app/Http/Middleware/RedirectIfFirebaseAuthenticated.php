<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfFirebaseAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('auth_uid') || session()->has('firebase_uid')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
