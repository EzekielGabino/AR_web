<?php

namespace App\Services;

/**
 * Push notifications stub.
 * Firebase Admin / FCM was removed to avoid the PHP gRPC extension on Render.
 * In-app alerts still write to Supabase student_notifications / teacher tables.
 */
class FcmPushService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(string $userId, string $title, string $body, array $data = []): void
    {
        // Intentionally no-op: device push disabled without Firebase.
        // Prefer Supabase notification rows (already inserted by callers).
    }
}
