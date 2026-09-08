<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->query('action');

        $entries = AuditEntry::query()
            ->when($action, fn ($q, $a) => $q->where('action', 'like', $a.'.%'))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $actions = AuditEntry::query()
            ->get(['action'])
            ->map(fn (AuditEntry $e) => $e->group())
            ->unique()->sort()->values();

        return view('audit.index', [
            'entries' => $entries,
            'actions' => $actions,
            'filters' => $request->only(['action']),
        ]);
    }
}
