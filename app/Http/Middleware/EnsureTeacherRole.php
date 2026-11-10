<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Teacher portal — blocks pure admin (and students) from teacher tools.
 * Admins are redirected to the admin dashboard.
 */
class EnsureTeacherRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('auth_uid') && ! session()->has('firebase_uid')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in first.',
            ]);
        }

        $role = strtolower((string) session('user_role', 'teacher'));
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        if ($role === 'student') {
            return redirect()->route('welcome');
        }
        if (! in_array($role, ['teacher', 'educator', ''], true)) {
            return redirect()->route('welcome');
        }

        return $next($request);
    }
}
