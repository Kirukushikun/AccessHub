<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProjectController;
use App\Services\DirectoryClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Admin console — the whole hub is admin-only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // People
    Route::get('/people', [PeopleController::class, 'index'])->name('people.index');
    Route::get('/people/add', [PeopleController::class, 'create'])->name('people.create');
    Route::post('/people', [PeopleController::class, 'store'])->name('people.store');
    Route::post('/people/bulk-scope', [PeopleController::class, 'bulkScope'])->name('people.bulk-scope');
    Route::get('/people/{person}/edit', [PeopleController::class, 'edit'])->name('people.edit');
    Route::put('/people/{person}', [PeopleController::class, 'update'])->name('people.update');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/new', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/connection-code', [ProjectController::class, 'generateCode'])->name('projects.connection-code');

    // Connections
    Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::post('/connections/{connection}/revoke', [ConnectionController::class, 'revoke'])->name('connections.revoke');
    Route::post('/connections/{connection}/regenerate', [ConnectionController::class, 'regenerate'])->name('connections.regenerate');

    // Audit log
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');

    // Admins
    Route::get('/admins', [AdminController::class, 'index'])->name('admins.index');
    Route::post('/admins', [AdminController::class, 'store'])->name('admins.store');
    Route::put('/admins/{user}', [AdminController::class, 'update'])->name('admins.update');

    /*
    | TEMPORARY — directory API sanity check. Delete before go-live.
    | Dumps the raw response plus a decrypt check of the first record's id.
    | An APP_KEY mismatch with the directory system is otherwise silent.
    */
    Route::get('/debug/user-api', function (DirectoryClient $directory) {
        abort_if(app()->isProduction(), 404);

        $endpoint = (string) config('services.user_api.endpoint');
        $key = (string) config('services.user_api.key');

        if ($endpoint === '' || $key === '') {
            return response()->json(['status' => 'not_configured', 'hint' => 'Set USER_API_ENDPOINT and USER_API_KEY.']);
        }

        $response = Http::withHeaders(['x-api-key' => $key])
            ->withOptions(['verify' => storage_path('cacert.pem')])
            ->timeout(10)->connectTimeout(5)
            ->post($endpoint);

        $body = $response->json() ?? $response->body();
        $first = data_get($body, 'data.0') ?? (is_array($body) ? ($body[0] ?? null) : null);

        return response()->json([
            'status' => $response->status(),
            'first_record_decrypt_check' => rescue(
                fn () => ['id_decrypts_to' => (int) Crypt::decryptString($first['id'] ?? '')],
                'FAILED — APP_KEY mismatch with the directory system',
                false,
            ),
            'normalised_sample' => rescue(fn () => $directory->users()->take(2), null, false),
            'raw' => $body,
        ], 200, [], JSON_PRETTY_PRINT);
    })->name('debug.user-api');
});
