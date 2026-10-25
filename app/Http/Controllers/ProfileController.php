<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class ProfileController extends Controller
{
    protected $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }

    // Attaches (or updates) a password credential on the teacher's
    // EXISTING Firebase Auth account — this is account linking, not
    // registration. It targets the uid already sitting in the session
    // (set at Google sign-in), so it can never create a second account
    // for the same teacher. After this, they can sign in via either
    // Google or email/password and land on the exact same uid/profile.
    public function updatePassword(Request $request)
    {
        $uid = session('firebase_uid');

        if (!$uid) {
            return response()->json(['message' => 'You must be signed in to do this.'], 401);
        }

        $request->validate([
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|string|same:password',
        ]);

        try {
            $this->auth->updateUser($uid, [
                'password' => $request->password,
            ]);

            return response()->json(['message' => 'Password set successfully. You can now sign in with email too.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to set password: ' . $e->getMessage()], 500);
        }
    }
}