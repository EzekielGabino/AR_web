<?php

namespace App\Services\Supabase;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Supabase Auth API helpers (server-side).
 * Validates user tokens via Auth API (no local JWT parsing required).
 */
class SupabaseAuth
{
    public function __construct(
        protected SupabaseClient $client,
    ) {}

    protected function base(): string
    {
        $url = SupabaseClient::normalizeProjectUrl((string) config('supabase.url'));
        if ($url === '') {
            throw new RuntimeException('SUPABASE_URL is not configured.');
        }

        return $url;
    }

    protected function anonKey(): string
    {
        $key = (string) config('supabase.anon_key');
        if ($key === '') {
            throw new RuntimeException('SUPABASE_ANON_KEY is not configured.');
        }

        return $key;
    }

    /**
     * Validate access_token and return Auth user payload.
     *
     * @return array<string, mixed>
     */
    public function userFromAccessToken(string $accessToken): array
    {
        $response = Http::baseUrl($this->base())
            ->withHeaders([
                'apikey' => $this->anonKey(),
                'Authorization' => 'Bearer '.$accessToken,
            ])
            ->acceptJson()
            ->get('/auth/v1/user');

        if (! $response->successful()) {
            throw new RuntimeException(
                'Invalid or expired session (HTTP '.$response->status().').'
            );
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['id'])) {
            throw new RuntimeException('Unexpected Auth user payload.');
        }

        return $data;
    }

    /**
     * Email/password sign-in.
     *
     * @return array{access_token: string, refresh_token?: string, user: array}
     */
    public function signInWithPassword(string $email, string $password): array
    {
        $response = Http::baseUrl($this->base())
            ->withHeaders([
                'apikey' => $this->anonKey(),
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->post('/auth/v1/token?grant_type=password', [
                'email' => $email,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Invalid email or password.');
        }

        $data = $response->json();
        if (empty($data['access_token']) || empty($data['user']['id'])) {
            throw new RuntimeException('Unexpected sign-in response.');
        }

        return $data;
    }

    /**
     * Email/password sign-up (teacher).
     * Uses Admin API when service role is configured so the email is
     * pre-confirmed (avoids "Confirm email" blocking immediate sign-in).
     *
     * @return array<string, mixed> Prefer shape with access_token + user when possible
     */
    public function signUp(string $email, string $password, string $name): array
    {
        // Preferred path: service role creates a confirmed user
        if ($this->client->serviceConfigured()) {
            $admin = $this->client->service()
                ->acceptJson()
                ->post('/auth/v1/admin/users', [
                    'email' => $email,
                    'password' => $password,
                    'email_confirm' => true,
                    'user_metadata' => [
                        'full_name' => $name,
                        'name' => $name,
                        'role' => 'teacher',
                    ],
                ]);

            if (! $admin->successful()) {
                $msg = $admin->json('msg')
                    ?? $admin->json('error_description')
                    ?? $admin->json('message')
                    ?? 'Registration failed.';
                // User already exists → fall through to password sign-in attempt by caller
                throw new RuntimeException(is_string($msg) ? $msg : 'Registration failed.');
            }

            // Admin create returns the user object (not a session). Caller signs in next.
            $user = $admin->json();
            if (isset($user['id'])) {
                return ['user' => $user];
            }

            return is_array($user) ? $user : [];
        }

        // Fallback: public signup (may require email confirmation)
        $response = Http::baseUrl($this->base())
            ->withHeaders([
                'apikey' => $this->anonKey(),
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->post('/auth/v1/signup', [
                'email' => $email,
                'password' => $password,
                'data' => [
                    'full_name' => $name,
                    'name' => $name,
                    'role' => 'teacher',
                ],
            ]);

        if (! $response->successful()) {
            $msg = $response->json('msg')
                ?? $response->json('error_description')
                ?? $response->json('message')
                ?? 'Registration failed.';
            throw new RuntimeException(is_string($msg) ? $msg : 'Registration failed.');
        }

        return $response->json() ?? [];
    }

    /**
     * Upsert profile via service role (ensures role/teacher fields).
     *
     * @param  array<string, mixed>  $authUser  from /auth/v1/user
     * @return array<string, mixed>
     */
    public function upsertTeacherProfile(array $authUser): array
    {
        $id = $authUser['id'];
        $meta = $authUser['user_metadata'] ?? [];
        $email = $authUser['email'] ?? null;
        $name = $meta['full_name'] ?? $meta['name'] ?? $email ?? 'Teacher';
        $photo = $meta['avatar_url'] ?? $meta['picture'] ?? null;

        $row = [
            'id' => $id,
            'email' => $email,
            'display_name' => $name,
            'photo_url' => $photo,
            'role' => 'teacher',
            'updated_at' => now()->toIso8601String(),
        ];

        $response = $this->client->service()
            ->withHeaders([
                'Prefer' => 'resolution=merge-duplicates,return=representation',
            ])
            ->post('/rest/v1/profiles', $row);

        if (! $response->successful()) {
            // Fallback: patch if exists
            $patch = $this->client->service()
                ->withHeaders(['Prefer' => 'return=representation'])
                ->patch('/rest/v1/profiles?id=eq.'.$id, [
                    'email' => $email,
                    'display_name' => $name,
                    'photo_url' => $photo,
                    'updated_at' => now()->toIso8601String(),
                ]);

            if (! $patch->successful()) {
                throw new RuntimeException(
                    'Could not upsert profile: HTTP '.$response->status().' '.$response->body()
                );
            }

            $data = $patch->json();
        } else {
            $data = $response->json();
        }

        if (is_array($data) && isset($data[0])) {
            return $data[0];
        }

        return is_array($data) ? $data : $row;
    }

    /**
     * Load profile by id (service role).
     *
     * @return array<string, mixed>|null
     */
    public function getProfile(string $id): ?array
    {
        $response = $this->client->service()
            ->get('/rest/v1/profiles?id=eq.'.$id.'&select=*&limit=1');

        if (! $response->successful()) {
            return null;
        }

        $rows = $response->json();

        return is_array($rows) && isset($rows[0]) ? $rows[0] : null;
    }
}
