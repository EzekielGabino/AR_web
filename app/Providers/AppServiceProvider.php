<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Firebase/Firestore removed — app uses Supabase (no gRPC).
    }

    public function boot(): void
    {
        // Render (and other reverse proxies): request looks like HTTP internally.
        // Without this, session cookies / signed URLs break and OAuth returns to login.
        $appUrl = (string) config('app.url', '');
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
