<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supabase project
    |--------------------------------------------------------------------------
    |
    | Create a project at https://supabase.com/dashboard then copy:
    | Project Settings → API → Project URL, anon public key, service_role key.
    |
    | Never expose the service_role key to the browser or mobile clients.
    |
    */

    'url' => rtrim((string) env('SUPABASE_URL', ''), '/'),

    'anon_key' => env('SUPABASE_ANON_KEY', ''),

    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | REST / Auth endpoints (derived from URL)
    |--------------------------------------------------------------------------
    */

    'rest_url' => rtrim((string) env('SUPABASE_URL', ''), '/') . '/rest/v1',

    'auth_url' => rtrim((string) env('SUPABASE_URL', ''), '/') . '/auth/v1',

    'storage_url' => rtrim((string) env('SUPABASE_URL', ''), '/') . '/storage/v1',

    /*
    |--------------------------------------------------------------------------
    | Optional: Storage bucket for activity attachments (created in Step 6)
    |--------------------------------------------------------------------------
    */

    'storage_bucket_attachments' => env('SUPABASE_BUCKET_ATTACHMENTS', 'activity-attachments'),

];
