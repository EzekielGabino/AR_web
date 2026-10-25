<?php

namespace App\Services\Supabase;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Thin PostgREST helpers using the service role (server-side only).
 */
class SupabaseRest
{
    public function __construct(
        protected SupabaseClient $client,
    ) {}

    /**
     * @param  array<string, string|int|bool|null>  $query
     */
    public function get(string $table, array $query = [], ?string $select = '*'): array
    {
        $qs = array_merge(['select' => $select], $query);
        $response = $this->client->service()->get('/rest/v1/'.$table, $qs);
        $this->throwIfFailed($response, 'GET '.$table);

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function insert(string $table, array $row): array
    {
        $response = $this->client->service()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post('/rest/v1/'.$table, $row);
        $this->throwIfFailed($response, 'INSERT '.$table);
        $data = $response->json();

        return is_array($data) && isset($data[0]) ? $data[0] : (is_array($data) ? $data : []);
    }

    /**
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>|null
     */
    public function update(string $table, string $idColumn, string $id, array $patch): ?array
    {
        $response = $this->client->service()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->patch('/rest/v1/'.$table.'?'.$idColumn.'=eq.'.$id, $patch);
        $this->throwIfFailed($response, 'UPDATE '.$table);
        $data = $response->json();

        return is_array($data) && isset($data[0]) ? $data[0] : null;
    }

    public function delete(string $table, string $idColumn, string $id): void
    {
        $response = $this->client->service()
            ->delete('/rest/v1/'.$table.'?'.$idColumn.'=eq.'.$id);
        $this->throwIfFailed($response, 'DELETE '.$table);
    }

    protected function throwIfFailed(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            $action.' failed (HTTP '.$response->status().'): '.$response->body()
        );
    }
}
