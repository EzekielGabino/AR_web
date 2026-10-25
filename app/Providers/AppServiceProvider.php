<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Firestore;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 1. Resolve absolute path to the service account key file
        $serviceAccountPath = storage_path('app/private/firebase/service-account.json');

        // 2. Pass credentials path directly to Google Cloud SDK environment variables
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $serviceAccountPath);
        $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = $serviceAccountPath;
        $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] = $serviceAccountPath;

        // 3. Register Firebase Factory Singleton
        $this->app->singleton(Factory::class, function () use ($serviceAccountPath) {
            return (new Factory)->withServiceAccount($serviceAccountPath);
        });

        // 4. Register Auth Singleton
        $this->app->singleton(Auth::class, function ($app) {
            return $app->make(Factory::class)->createAuth();
        });

        // 5. Register Firestore Singleton
        $this->app->singleton(Firestore::class, function ($app) {
            return $app->make(Factory::class)->createFirestore();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
