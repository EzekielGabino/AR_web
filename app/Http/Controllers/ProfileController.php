<?php

namespace App\Http\Controllers;

use App\Services\Supabase\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Teacher profile actions (Supabase Auth — no Firebase).
 */
class ProfileController extends Controller
{
    public function updatePassword(Request $request)
    {
        $uid = $request->session()->get('auth_uid')
            ?: $request->session()->get('firebase_uid');

        if (! $uid) {
            return response()->json([
                'message' => 'You must be signed in to change your password.',
            ], 401);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $url = SupabaseClient::normalizeProjectUrl((string) config('supabase.url'));
            $serviceKey = (string) config('supabase.service_role_key');

            if ($url === '' || $serviceKey === '') {
                return response()->json([
                    'message' => 'Password service is not configured (Supabase keys missing).',
                ], 503);
            }

            $response = Http::baseUrl($url)
                ->withHeaders([
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer '.$serviceKey,
                    'Content-Type' => 'application/json',
                ])
                ->put('/auth/v1/admin/users/'.$uid, [
                    'password' => $validated['password'],
                ]);

            if (! $response->successful()) {
                $msg = $response->json('msg')
                    ?? $response->json('message')
                    ?? ('Could not update password (HTTP '.$response->status().').');

                return response()->json([
                    'message' => is_string($msg) ? $msg : 'Could not update password.',
                ], 422);
            }

            try {
                app(SupabaseClient::class)->service()
                    ->patch('/rest/v1/profiles?id=eq.'.$uid, [
                        'has_password_set' => true,
                        'updated_at' => now()->toIso8601String(),
                    ]);
            } catch (Throwable $e) {
                // non-fatal
            }

            return response()->json([
                'message' => 'Password updated. You can sign in with email and this password.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: 'Could not update password.',
            ], 500);
        }
    }
}
