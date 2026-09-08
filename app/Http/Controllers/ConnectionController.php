<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConnectionController extends Controller
{
    public function index(): View
    {
        return view('connections.index', [
            'grouped' => Connection::with('project')
                ->get()
                ->sortBy(fn (Connection $c) => [$c->project->name, $c->environment])
                ->groupBy(fn (Connection $c) => $c->project->name),
        ]);
    }

    public function revoke(Connection $connection): RedirectResponse
    {
        if ($connection->isRevoked()) {
            return back()->with('info', 'That connection is already revoked.');
        }

        $connection->forceFill(['revoked_at' => now()])->save();

        Audit::record('connection.revoked',
            "{$connection->project->name} · {$connection->environment}",
            $connection->project);

        return back()->with('success', 'Connection revoked. The project must re-enroll with a new code.');
    }

    public function regenerate(Connection $connection): RedirectResponse
    {
        if ($connection->isRevoked()) {
            return back()->with('info', 'Revoked connections cannot be rotated — issue a new code instead.');
        }

        $secret = Str::random(48);
        $connection->forceFill(['client_secret_hash' => Hash::make($secret)])->save();

        Audit::record('connection.secret_rotated',
            "{$connection->project->name} · {$connection->environment}",
            $connection->project);

        return back()->with([
            'success' => 'New secret generated. It is shown once — update the project immediately.',
            'connection_code' => $secret,
        ]);
    }
}
