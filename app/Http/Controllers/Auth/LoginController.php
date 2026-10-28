<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Supabase\SupabaseAuth;
use Illuminate\Http\Request;
use Throwable;

/**
 * Teacher email/password Auth via Supabase (migration Step 4).
 */
class LoginController extends Controller
{
    public function __construct(
        protected SupabaseAuth $supabaseAuth,
    ) {}

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $displayName = strstr($request->email, '@', true) ?: $request->email;

        try {
            $signUp = $this->supabaseAuth->signUp(
                $request->email,
                $request->password,
                $displayName
            );

            // Admin signup returns user only; public signup may return a session.
            if (! empty($signUp['access_token']) && ! empty($signUp['user']['id'])) {
                $tokenPayload = $signUp;
            } else {
                try {
                    $tokenPayload = $this->supabaseAuth->signInWithPassword(
                        $request->email,
                        $request->password
                    );
                } catch (Throwable $e) {
                    return back()
                        ->withInput()
                        ->with('auth_mode', 'register')
                        ->withErrors([
                            'email' => 'Account was created but automatic sign-in failed: '.$e->getMessage()
                                .'. Try Sign in with the same email and password. '
                                .'If it still fails, delete the user in Supabase Authentication → Users and register again.',
                        ]);
                }
            }

            $authUser = $tokenPayload['user'] ?? null;
            if (! is_array($authUser) || empty($authUser['id'])) {
                return back()
                    ->withInput()
                    ->with('auth_mode', 'register')
                    ->withErrors(['email' => 'Registration response was incomplete. Check Supabase Auth logs.']);
            }
            $profile = $this->supabaseAuth->upsertTeacherProfile([
                'id' => $authUser['id'],
                'email' => $authUser['email'] ?? $request->email,
                'user_metadata' => array_merge($authUser['user_metadata'] ?? [], [
                    'full_name' => $displayName,
                    'role' => 'teacher',
                ]),
            ]);

            // Mark password set for email registrations
            try {
                app(\App\Services\Supabase\SupabaseClient::class)->service()
                    ->patch('/rest/v1/profiles?id=eq.'.$authUser['id'], [
                        'has_password_set' => true,
                        'display_name' => $displayName,
                        'role' => 'teacher',
                    ]);
                $profile['has_password_set'] = true;
            } catch (Throwable $e) {
                // non-fatal
            }

            $this->putSession($request, $authUser, $profile);

            return redirect()->route('dashboard');
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('auth_mode', 'register')
                ->withErrors([
                    'email' => $e->getMessage() ?: 'Registration failed.',
                ]);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $tokenPayload = $this->supabaseAuth->signInWithPassword(
                $request->email,
                $request->password
            );

            $authUser = $tokenPayload['user'];
            $profile = $this->supabaseAuth->getProfile($authUser['id']);
            if (! $profile) {
                $profile = $this->supabaseAuth->upsertTeacherProfile([
                    'id' => $authUser['id'],
                    'email' => $authUser['email'] ?? $request->email,
                    'user_metadata' => $authUser['user_metadata'] ?? [],
                ]);
            }

            $role = strtolower($profile['role'] ?? 'teacher');
            if ($role !== 'teacher' && $role !== 'educator') {
                return back()->withInput()->withErrors([
                    'email' => 'Access Restricted: The Web Dashboard is for teachers only. Students must use the SciLens AR Mobile App.',
                ]);
            }

            $this->putSession($request, $authUser, $profile);

            return redirect()->route('dashboard');
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'email' => 'Invalid email or password.',
            ]);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'auth_uid',
            'auth_provider',
            'firebase_uid',
            'teacher_name',
            'teacher_email',
            'teacher_photo_url',
            'teacher_member_since',
            'user_role',
            'needs_password_setup',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * @param  array<string, mixed>  $authUser
     * @param  array<string, mixed>  $profile
     */
    protected function putSession(Request $request, array $authUser, array $profile): void
    {
        $uid = $authUser['id'];
        $request->session()->put([
            'auth_uid' => $uid,
            'auth_provider' => 'supabase',
            'firebase_uid' => $uid,
            'teacher_name' => $profile['display_name'] ?? $request->input('name') ?? 'Teacher',
            'teacher_email' => $profile['email'] ?? $request->input('email'),
            'teacher_photo_url' => $profile['photo_url'] ?? null,
            'teacher_member_since' => $profile['created_at'] ?? null,
            'user_role' => $profile['role'] ?? 'teacher',
        ]);
        $request->session()->regenerate();
    }
}
