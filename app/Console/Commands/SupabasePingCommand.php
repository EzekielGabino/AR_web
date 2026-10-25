<?php

namespace App\Console\Commands;

use App\Services\Supabase\SupabaseClient;
use Illuminate\Console\Command;

/**
 * Step 2 helper: verify .env keys can reach the Supabase project.
 *
 *   php artisan supabase:ping
 */
class SupabasePingCommand extends Command
{
    protected $signature = 'supabase:ping';

    protected $description = 'Check Supabase URL / anon key connectivity (migration Step 2)';

    public function handle(SupabaseClient $supabase): int
    {
        $result = $supabase->healthCheck();

        if ($result['ok']) {
            $this->info('OK — '.$result['message']);
            if (! empty($result['url'])) {
                $this->line('URL: '.$result['url']);
            }
            if (isset($result['status'])) {
                $this->line('HTTP: '.$result['status']);
            }

            return self::SUCCESS;
        }

        $this->error('FAIL — '.$result['message']);
        if (! empty($result['url'])) {
            $this->line('URL: '.$result['url']);
        }

        $this->newLine();
        $this->comment('1. Create a project at https://supabase.com/dashboard');
        $this->comment('2. Project Settings → API → copy URL + anon key + service_role key');
        $this->comment('3. Put them in .env (SUPABASE_URL, SUPABASE_ANON_KEY, SUPABASE_SERVICE_ROLE_KEY)');
        $this->comment('4. Run: php artisan config:clear && php artisan supabase:ping');

        return self::FAILURE;
    }
}
