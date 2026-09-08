<?php

use App\Http\Controllers\Api\EnrollController;
use App\Http\Controllers\Api\GrantsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // The code is the auth. Rate limited, logs source IP.
    Route::post('/enroll', EnrollController::class)
        ->middleware('throttle:10,1')
        ->name('api.enroll');

    // Client-credentials auth via X-Client-Id / X-Client-Secret headers.
    Route::get('/grants', GrantsController::class)
        ->middleware(['connection.auth', 'throttle:60,1'])
        ->name('api.grants');
});
