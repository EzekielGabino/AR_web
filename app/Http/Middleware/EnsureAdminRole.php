<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin portal only — requires signed-in session with role=admin.
 */
class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('auth_uid') && ! session()->has('firebase_uid')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in first.',
            ]);
        }

        $role = strtolower((string) session('user_role', ''));
        if ($role === 'student') {
            return redirect()->to(route('welcome').'?auth_notice=student#login');
        }
        if ($role !== 'admin') {
            return redirect()->route('dashboard')->withErrors([
                'email' => 'Admin access only.',
            ]);
        }

        return $next($request);
    }
}
