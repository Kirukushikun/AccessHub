<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

/**
 * In-app reference for hub admins: how to onboard a new project. Written for
 * the person clicking buttons in this UI — the developer-facing contract for
 * whoever builds the other side lives in docs/api.md and
 * docs/hub-integration-guide.md in the repo.
 */
class GuideController extends Controller
{
    public function __invoke(): View
    {
        return view('guide.index', [
            'hubUrl' => config('app.url'),
            'hasProjects' => Project::exists(),
        ]);
    }
}
