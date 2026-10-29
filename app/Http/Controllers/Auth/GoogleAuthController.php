<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Legacy Firebase Google callback — disabled.
 * Web Google sign-in uses Supabase OAuth (auth.supabase.callback / session).
 */
class GoogleAuthController extends Controller
{
    public function callback(Request $request)
    {
        return response()->json([
            'message' => 'Firebase Google sign-in is disabled. Use Supabase Google OAuth on the home page.',
        ], 410);
    }
}
