<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use App\Models\Connection;
use App\Models\Person;
use App\Models\Project;
use App\Support\AccessHub;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $staleBefore = now()->subDays(AccessHub::staleAfterDays());

        return view('dashboard.index', [
            'stats' => [
                'people_active' => Person::where('active', true)->count(),
                'people_inactive' => Person::where('active', false)->count(),
                'projects_active' => Project::where('active', true)->count(),
                'connections_live' => Connection::live()->count(),
                'connections_stale' => Connection::live()
                    ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $staleBefore))
                    ->count(),
            ],
            'connections' => Connection::with('project')->live()
                ->orderByRaw('last_seen_at is null desc')
                ->orderBy('last_seen_at')
                ->get(),
            'recentActivity' => AuditEntry::latest('created_at')->limit(5)->get(),
        ]);
    }
}
