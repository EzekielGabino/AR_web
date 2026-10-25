<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class GoogleAuthController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct(FirebaseAuth $auth, Firestore $firestore)
    {
        $this->auth = $auth;
        $this->firestore = $firestore;
    }

    public function callback(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $verifiedToken = $this->auth->verifyIdToken($request->id_token);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['message' => 'Invalid or expired sign-in token.'], 401);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not verify sign-in token.'], 401);
        }

        $uid = $verifiedToken->claims()->get('sub');
        $email = $verifiedToken->claims()->get('email');
        $name = $verifiedToken->claims()->get('name') ?? $email;
        $photoUrl = $verifiedToken->claims()->get('picture');

        $userDocRef = $this->firestore->database()
            ->collection('users')
            ->document($uid);

        $userDocument = $userDocRef->snapshot();

        // CHANGED: capture this before the branch, since it's what
        // decides whether we route to the password-setup step below.
        $isNewUser = !$userDocument->exists();

        if (!$isNewUser) {
            $userData = $userDocument->data();
            $role = strtolower($userData['role'] ?? '');

            if ($role !== 'teacher' && $role !== 'educator') {
                return response()->json([
                    'message' => 'Access Restricted: The Web Dashboard is for teachers only. Students must use the SciLens AR Mobile App.',
                ], 403);
            }

            $teacherName = $userData['name'] ?? $name;

            if ($photoUrl && ($userData['photoUrl'] ?? null) !== $photoUrl) {
                $userDocRef->set(['photoUrl' => $photoUrl], ['merge' => true]);
                $userData['photoUrl'] = $photoUrl;
            }
        } else {
            $userData = [
                'uid' => $uid,
                'name' => $name,
                'email' => $email,
                'photoUrl' => $photoUrl,
                'role' => 'teacher',
                'created_at' => date('c'),
                // NEW: tracks whether this account has a usable email/password
                // login yet. Firebase accounts created via Google sign-in have
                // NO password by default — this flag drives the set-password
                // prompt below, independent of session state, so it survives
                // even if the session expires before the teacher finishes it.
                'has_password_set' => false,
            ];

            $userDocRef->set($userData);
            $teacherName = $name;
        }

        session([
            'firebase_uid' => $uid,
            'teacher_name' => $teacherName,
            'teacher_email' => $email,
            'teacher_photo_url' => $userData['photoUrl'] ?? null,
            'teacher_member_since' => $userData['created_at'] ?? null,
            'user_role' => $userData['role'] ?? 'teacher',
        ]);

        // NEW: route new Google sign-ins through the password-setup step
        // instead of straight to the dashboard. Returning users (or anyone
        // who already has has_password_set = true) skip it entirely.
        $needsPasswordSetup = $isNewUser || !($userData['has_password_set'] ?? false);

        if ($needsPasswordSetup) {
            session(['needs_password_setup' => true]);
            return response()->json(['redirect' => route('auth.set-password')]);
        }

        return response()->json(['redirect' => route('dashboard')]);
    }

    // NEW: shows the "set a password" form. Guarded by the session flag
    // set in callback() above — visiting this URL directly without having
    // just signed in via Google harmlessly bounces to the dashboard.
    public function showSetPassword(Request $request)
    {
        if (!$request->session()->get('needs_password_setup') || !$request->session()->has('firebase_uid')) {
            return redirect()->route('dashboard');
        }

        return view('auth.set-password');
    }

    // NEW: applies the chosen password to the Firebase Auth account via
    // the Admin SDK, then clears the flag so this never shows again for
    // this teacher (also flips has_password_set in Firestore, which is
    // the durable record — session state alone wouldn't survive logout).
    public function setPassword(Request $request)
    {
        if (!$request->session()->get('needs_password_setup') || !$request->session()->has('firebase_uid')) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $uid = $request->session()->get('firebase_uid');

        try {
            // NOTE: method name depends on your kreait/laravel-firebase
            // version. changeUserPassword() is the dedicated helper on
            // Contract\Auth in recent versions. If your installed version
            // doesn't have it, use:
            //   $this->auth->updateUser($uid, ['password' => $request->password]);
            $this->auth->changeUserPassword($uid, $request->password);

            $this->firestore->database()
                ->collection('users')
                ->document($uid)
                ->set(['has_password_set' => true], ['merge' => true]);
        } catch (\Throwable $e) {
            return back()->withErrors(['password' => 'Failed to set password: ' . $e->getMessage()]);
        }

        $request->session()->forget('needs_password_setup');

        return redirect()->route('dashboard');
    }

    // NEW: lets a teacher defer this — they'll be prompted again next
    // Google sign-in since has_password_set stays false in Firestore.
    public function skipPasswordSetup(Request $request)
    {
        $request->session()->forget('needs_password_setup');
        return redirect()->route('dashboard');
    }
}