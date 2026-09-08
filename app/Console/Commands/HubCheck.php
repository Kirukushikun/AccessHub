<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DirectoryClient;
use App\Services\DirectoryUnavailable;
use Illuminate\Console\Command;

/**
 * Post-deploy sanity check. Run this once on the server after uploading:
 *
 *   php artisan hub:check
 *
 * Confirms the directory API is reachable and that this app's APP_KEY matches
 * the directory system (a mismatch is otherwise silent — every record is skipped).
 */
class HubCheck extends Command
{
    protected $signature = 'hub:check';

    protected $description = 'Verify environment, directory API connectivity, and APP_KEY match';

    public function handle(DirectoryClient $directory): int
    {
        $ok = true;

        // --- Environment ---
        $this->line('<info>Environment</info>');
        $this->kv('APP_ENV', config('app.env'));
        $this->kv('APP_DEBUG', config('app.debug') ? 'true  <comment>(should be false in production)</comment>' : 'false');
        $this->kv('APP_URL', config('app.url'));
        $ok = $this->assert('APP_KEY is set', filled(config('app.key'))) && $ok;
        $ok = $this->assert('storage/cacert.pem present', is_file(storage_path('cacert.pem'))) && $ok;

        if (config('app.env') === 'production') {
            $ok = $this->assert('APP_DEBUG is off', ! config('app.debug')) && $ok;
            $ok = $this->assert('SESSION_SECURE_COOKIE is on', (bool) config('session.secure')) && $ok;
        }

        // --- Admin ---
        $this->newLine();
        $this->line('<info>Admin</info>');
        $adminCount = User::where('active', true)->count();
        $ok = $this->assert("at least one active admin exists ({$adminCount})", $adminCount > 0) && $ok;

        // --- Directory API ---
        $this->newLine();
        $this->line('<info>Directory API</info>');
        $this->kv('USER_API_ENDPOINT', config('services.user_api.endpoint') ?: '<comment>(not set)</comment>');

        try {
            $users = $directory->users();
            $this->assert('directory reachable', true);
            $this->assert("records decrypt with this APP_KEY ({$users->count()} people)", $users->isNotEmpty());

            if ($first = $users->first()) {
                $this->line("  sample: #{$first['user_id']}  {$first['name']}  {$first['email']}");
            } else {
                $this->warn('  0 records decoded — likely an APP_KEY mismatch with the directory system.');
                $ok = false;
            }
        } catch (DirectoryUnavailable $e) {
            $this->error('  '.$e->getMessage());
            $ok = false;
        }

        $this->newLine();
        $this->line($ok ? '<info>All checks passed.</info>' : '<error>Some checks failed — see above.</error>');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function kv(string $key, string $value): void
    {
        $this->line(sprintf('  %-24s %s', $key, $value));
    }

    private function assert(string $label, bool $pass): bool
    {
        $this->line(sprintf('  [%s] %s', $pass ? '<info>PASS</info>' : '<error>FAIL</error>', $label));

        return $pass;
    }
}
