<?php

namespace App\Services\Supabase;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Supabase REST client for server-side use (Laravel).
 * Step 2: connectivity only — Auth/schema come in later steps.
 */
class SupabaseClient
{
    public function __construct(
        protected ?string $url = null,
        protected ?string $anonKey = null,
        protected ?string $serviceRoleKey = null,
    ) {
        $this->url = self::normalizeProjectUrl($url ?? (string) config('supabase.url'));
        $this->anonKey = $anonKey ?? (string) config('supabase.anon_key');
        $this->serviceRoleKey = $serviceRoleKey ?? (string) config('supabase.service_role_key');
    }

    /**
     * Project origin only, e.g. https://xxxx.supabase.co
     * Strips accidental /rest/v1 (Data API copy) and accepts bare project refs.
     */
    public static function normalizeProjectUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (! str_contains($url, '://') && ! str_starts_with($url, '//')) {
            if (preg_match('/^[a-z0-9]+$/i', $url)) {
                $url = 'https://'.$url.'.supabase.co';
            }
        }

        $url = rtrim($url, '/');
        $url = preg_replace('#/rest/v1$#i', '', $url) ?? $url;
        $url = preg_replace('#/auth/v1$#i', '', $url) ?? $url;

        return rtrim($url, '/');
    }

    public function configured(): bool
    {
        return $this->url !== '' && $this->anonKey !== '';
    }

    public function serviceConfigured(): bool
    {
        return $this->configured() && $this->serviceRoleKey !== '';
    }

    /** Public (anon) client — respects RLS when policies exist. */
    public function anon(): PendingRequest
    {
        $this->assertConfigured();

        return Http::baseUrl($this->url)
            ->withHeaders([
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer '.$this->anonKey,
            ])
            ->acceptJson();
    }

    /** Service role — bypasses RLS. Server only; never ship to the browser. */
    public function service(): PendingRequest
    {
        $this->assertConfigured();
        if ($this->serviceRoleKey === '') {
            throw new RuntimeException('SUPABASE_SERVICE_ROLE_KEY is not set.');
        }

        return Http::baseUrl($this->url)
            ->withHeaders([
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer '.$this->serviceRoleKey,
            ])
            ->acceptJson();
    }

    /**
     * Upload bytes to Storage. Returns public URL metadata.
     *
     * @return array{storagePath: string, downloadURL: string, name: string, mimeType: string, size: int}
     */
    public function uploadPublicObject(
        string $bucket,
        string $objectPath,
        string $contents,
        string $mimeType,
        string $originalName,
    ): array {
        $this->assertConfigured();
        if ($this->serviceRoleKey === '') {
            throw new RuntimeException('SUPABASE_SERVICE_ROLE_KEY is not set.');
        }

        $objectPath = ltrim($objectPath, '/');
        $response = Http::baseUrl($this->url)
            ->withHeaders([
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer '.$this->serviceRoleKey,
                'Content-Type' => $mimeType !== '' ? $mimeType : 'application/octet-stream',
                'x-upsert' => 'true',
            ])
            ->withBody($contents, $mimeType !== '' ? $mimeType : 'application/octet-stream')
            ->post('/storage/v1/object/'.$bucket.'/'.$objectPath);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Storage upload failed (HTTP '.$response->status().'): '.$response->body()
            );
        }

        $publicUrl = $this->url.'/storage/v1/object/public/'.$bucket.'/'.$objectPath;

        return [
            'storagePath' => $bucket.'/'.$objectPath,
            'downloadURL' => $publicUrl,
            'url' => $publicUrl,
            'name' => $originalName,
            'mimeType' => $mimeType,
            'size' => strlen($contents),
            'source' => 'supabase_storage',
            'uploadedAt' => now()->toIso8601String(),
        ];
    }

    public function deleteObject(string $bucket, string $objectPath): void
    {
        $this->assertConfigured();
        $objectPath = ltrim($objectPath, '/');
        $response = Http::baseUrl($this->url)
            ->withHeaders([
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer '.$this->serviceRoleKey,
            ])
            ->delete('/storage/v1/object/'.$bucket.'/'.$objectPath);

        // 404 = already gone
        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException(
                'Storage delete failed (HTTP '.$response->status().'): '.$response->body()
            );
        }
    }

    /**
     * Lightweight health check against Auth API.
     *
     * @return array{ok: bool, status?: int, message: string, url?: string}
     */
    public function healthCheck(): array
    {
        if (! $this->configured()) {
            return [
                'ok' => false,
                'message' => 'Supabase is not configured. Set SUPABASE_URL and SUPABASE_ANON_KEY in .env.',
            ];
        }

        try {
            $response = $this->anon()->get('/auth/v1/health');

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'status' => $response->status(),
                    'message' => 'Supabase Auth endpoint reachable.',
                    'url' => $this->url,
                ];
            }

            $settings = $this->anon()->get('/auth/v1/settings');
            if ($settings->successful()) {
                return [
                    'ok' => true,
                    'status' => $settings->status(),
                    'message' => 'Supabase Auth settings reachable.',
                    'url' => $this->url,
                ];
            }

            // Last resort: REST root often 200 with empty schema message
            $rest = $this->anon()->get('/rest/v1/');
            if ($rest->status() < 500) {
                return [
                    'ok' => true,
                    'status' => $rest->status(),
                    'message' => 'Supabase project reachable (REST). Auth health returned HTTP '.$response->status().'.',
                    'url' => $this->url,
                ];
            }

            return [
                'ok' => false,
                'status' => $response->status(),
                'message' => 'Supabase responded but health check failed: HTTP '.$response->status(),
                'url' => $this->url,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Could not reach Supabase: '.$e->getMessage(),
                'url' => $this->url,
            ];
        }
    }

    protected function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException(
                'Supabase is not configured. Set SUPABASE_URL and SUPABASE_ANON_KEY in .env.'
            );
        }
    }
}
