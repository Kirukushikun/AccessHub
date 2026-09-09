<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role key rename: "requestor" -> "manager". Rewrites the roles JSON on every
 * person that carries it. Idempotent — safe to run on a fresh DB (no rows) too.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rewrite('requestor', 'manager');
    }

    public function down(): void
    {
        $this->rewrite('manager', 'requestor');
    }

    private function rewrite(string $from, string $to): void
    {
        DB::table('people')->orderBy('id')->each(function ($person) use ($from, $to) {
            $roles = json_decode($person->roles ?? '[]', true) ?: [];

            if (! in_array($from, $roles, true)) {
                return;
            }

            $roles = array_values(array_unique(array_map(
                fn ($role) => $role === $from ? $to : $role,
                $roles,
            )));

            DB::table('people')->where('id', $person->id)->update([
                'roles' => json_encode($roles),
            ]);
        });
    }
};
