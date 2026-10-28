<?php

namespace App\Services;

use App\Services\Supabase\SupabaseRest;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

/**
 * Send FCM data/notification messages to registered student device tokens.
 */
class FcmPushService
{
    public function __construct(
        protected SupabaseRest $db,
    ) {}

    public function sendToUser(string $userId, string $title, string $body, array $data = []): void
    {
        try {
            $rows = $this->db->get('push_tokens', [
                'user_id' => 'eq.'.$userId,
                'select' => 'token',
            ]);
        } catch (Throwable $e) {
            Log::warning('FCM: could not load push_tokens: '.$e->getMessage());

            return;
        }

        if ($rows === []) {
            return;
        }

        try {
            $messaging = $this->messaging();
        } catch (Throwable $e) {
            Log::warning('FCM: messaging client unavailable: '.$e->getMessage().' — check FIREBASE_CREDENTIALS and Cloud Messaging API.');

            return;
        }

        $payload = [];
        foreach ($data as $k => $v) {
            $payload[(string) $k] = is_scalar($v) || $v === null ? (string) $v : json_encode($v);
        }

        Log::info('FCM: sending to user '.$userId.' ('.count($rows).' token(s))');

        foreach ($rows as $row) {
            $token = $row['token'] ?? null;
            if (! is_string($token) || $token === '') {
                continue;
            }
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(FcmNotification::create($title, $body))
                    ->withData($payload);

                $messaging->send($message);
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                Log::warning('FCM send failed for token: '.$msg);
                // Drop invalid tokens
                if (str_contains($msg, 'not found') || str_contains($msg, 'UNREGISTERED') || str_contains($msg, 'Invalid')) {
                    try {
                        $gone = $this->db->get('push_tokens', ['token' => 'eq.'.$token, 'select' => 'id', 'limit' => 1]);
                        if ($gone && ! empty($gone[0]['id'])) {
                            $this->db->delete('push_tokens', 'id', $gone[0]['id']);
                        }
                    } catch (Throwable) {
                        // ignore
                    }
                }
            }
        }
    }

    protected function messaging(): \Kreait\Firebase\Contract\Messaging
    {
        $path = config('firebase.credentials');
        if (! is_string($path) || $path === '' || ! is_file($path)) {
            // Fallbacks used in this project
            $candidates = [
                base_path('app/private/firebase/service-account.json'),
                storage_path('app/private/firebase/service-account.json'),
                base_path(env('FIREBASE_CREDENTIALS', '')),
            ];
            $path = null;
            foreach ($candidates as $c) {
                if (is_string($c) && $c !== '' && is_file($c)) {
                    $path = $c;
                    break;
                }
            }
        }
        if (! $path) {
            throw new \RuntimeException('Firebase service account JSON not found.');
        }

        return (new Factory)
            ->withServiceAccount($path)
            ->createMessaging();
    }
}
