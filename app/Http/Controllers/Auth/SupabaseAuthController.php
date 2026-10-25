<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Supabase\SupabaseAuth;
use Illuminate\Http\Request;
use Throwable;

/**
 * Teacher web Auth via Supabase (migration Step 4).
 * Browser obtains a Supabase access_token (Google OAuth or password);
 * this controller validates it and opens a Laravel session.
 */
class SupabaseAuthController extends Controller
{
    public function __construct(
        protected SupabaseAuth $supabaseAuth,
    ) {}

    /**
     * Browser posts { access_token } after Supabase Google OAuth or client sign-in.
     */
    public function sessionFromToken(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            $authUser = $this->supabaseAuth->userFromAccessToken($request->access_token);
            $profile = $this->supabaseAuth->upsertTeacherProfile($authUser);

            $role = strtolower($profile['role'] ?? 'teacher');
            if ($role !== 'teacher' && $role !== 'educator') {
                return response()->json([
                    'message' => 'Access Restricted: The Web Dashboard is for teachers only. Students must use the SciLens AR Mobile App.',
                ], 403);
            }

            $this->putSession($request, $authUser, $profile);

            $needsPassword = ! ($profile['has_password_set'] ?? false);
            if ($needsPassword) {
                $request->session()->put('needs_password_setup', true);

                return response()->json(['redirect' => route('auth.set-password')]);
            }

            return response()->json(['redirect' => route('dashboard')]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: 'Sign-in failed.',
            ], 401);
        }
    }

    /**
     * OAuth redirect landing page (hash/query tokens handled in JS).
     */
    public function callbackPage()
    {
        return view('auth.supabase-callback');
    }

    public function showSetPassword(Request $request)
    {
        if (! $request->session()->get('needs_password_setup') || ! $this->sessionUserId($request)) {
            return redirect()->route('dashboard');
        }

        return view('auth.set-password');
    }

    /**
     * Mark password as set on profile (actual password change via Supabase client optional).
     * For Google-only teachers we only flip has_password_set after they set one client-side,
     * or skip. Email/password users already have a password.
     */
    public function setPassword(Request $request)
    {
        if (! $request->session()->get('needs_password_setup') || ! $this->sessionUserId($request)) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $uid = $this->sessionUserId($request);

        try {
            // Update password through Supabase Admin API (service role)
            $url = \App\Services\Supabase\SupabaseClient::normalizeProjectUrl((string) config('supabase.url'));
            $serviceKey = (string) config('supabase.service_role_key');

            $response = \Illuminate\Support\Facades\Http::baseUrl($url)
                ->withHeaders([
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer '.$serviceKey,
                    'Content-Type' => 'application/json',
                ])
                ->put('/auth/v1/admin/users/'.$uid, [
                    'password' => $request->password,
                ]);

            if (! $response->successful()) {
                return back()->withErrors([
                    'password' => 'Could not update password: HTTP '.$response->status(),
                ]);
            }

            app(\App\Services\Supabase\SupabaseClient::class)->service()
                ->patch('/rest/v1/profiles?id=eq.'.$uid, [
                    'has_password_set' => true,
                    'updated_at' => now()->toIso8601String(),
                ]);

            $request->session()->forget('needs_password_setup');

            return redirect()->route('dashboard');
        } catch (Throwable $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }
    }

    public function skipPasswordSetup(Request $request)
    {
        $request->session()->forget('needs_password_setup');

        return redirect()->route('dashboard');
    }

    protected function sessionUserId(Request $request): ?string
    {
        return $request->session()->get('auth_uid')
            ?: $request->session()->get('firebase_uid');
    }

    /**
     * @param  array<string, mixed>  $authUser
     * @param  array<string, mixed>  $profile
     */
    protected function putSession(Request $request, array $authUser, array $profile): void
    {
        $uid = $authUser['id'];
        $name = $profile['display_name'] ?? $authUser['email'] ?? 'Teacher';
        $email = $profile['email'] ?? $authUser['email'] ?? null;

        // auth_uid = Supabase UUID. firebase_uid kept as alias for older middleware/views
        // until Step 5 fully drops Firebase session keys.
        $request->session()->put([
            'auth_uid' => $uid,
            'auth_provider' => 'supabase',
            'firebase_uid' => $uid,
            'teacher_name' => $name,
            'teacher_email' => $email,
            'teacher_photo_url' => $profile['photo_url'] ?? null,
            'teacher_member_since' => $profile['created_at'] ?? null,
            'user_role' => $profile['role'] ?? 'teacher',
        ]);

        $request->session()->regenerate();
    }
}
