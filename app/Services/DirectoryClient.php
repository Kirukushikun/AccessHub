<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads the org user directory — the same bulk list endpoint the auth systems'
 * User Management panels use. See docs/access-hub-requirements.md §8.
 *
 *  - POST (no body), header x-api-key
 *  - response is a BARE array, no data wrapper
 *  - names arrive split (first_name / last_name), compose them
 *  - ids arrive ENCRYPTED with the directory system's APP_KEY — decrypt each,
 *    skipping any record that fails (mismatched key throws per-record)
 */
class DirectoryClient
{
    /**
     * @return Collection<int, array{user_id: int, name: string, email: string, first_name: string, last_name: string, middle_name: ?string}>
     *
     * @throws DirectoryUnavailable
     */
    public function users(): Collection
    {
        $endpoint = (string) config('services.user_api.endpoint');
        $key = (string) config('services.user_api.key');

        if ($endpoint === '' || $key === '') {
            throw DirectoryUnavailable::notConfigured();
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $key])
                ->withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)
                ->connectTimeout(5)
                ->post($endpoint);
        } catch (ConnectionException $e) {
            Log::warning('Directory API unreachable', ['error' => $e->getMessage()]);
            throw DirectoryUnavailable::unreachable($e->getMessage());
        }

        if (! $response->successful()) {
            Log::warning('Directory API error', ['status' => $response->status()]);
            throw DirectoryUnavailable::unreachable("HTTP {$response->status()}");
        }

        $raw = $response->json();
        $rows = $raw['data'] ?? $raw ?? [];

        return collect($rows)
            ->map(fn (array $row) => $this->normalise($row))
            ->filter()
            ->values();
    }

    /** @return array{user_id: int, name: string, email: string, first_name: string, last_name: string, middle_name: ?string}|null */
    private function normalise(array $row): ?array
    {
        try {
            $id = (int) Crypt::decryptString((string) ($row['id'] ?? ''));
        } catch (\Throwable) {
            // Record encrypted with a different APP_KEY — skip it, don't fail the batch.
            return null;
        }

        if ($id <= 0) {
            return null;
        }

        $first = trim((string) ($row['first_name'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));
        $name = trim($first.' '.$last) ?: (string) ($row['name'] ?? '');

        return [
            'user_id' => $id,
            'name' => $name,
            'email' => (string) ($row['email'] ?? ''),
            'first_name' => $first,
            'last_name' => $last,
            'middle_name' => trim((string) ($row['middle_name'] ?? '')) ?: null,
        ];
    }
}
