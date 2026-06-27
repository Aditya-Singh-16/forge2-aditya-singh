<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are stateless and prefixed with /api. Authentication uses
| Laravel Sanctum bearer tokens. Once a request is authenticated, the active
| tenant (organization) is derived from the user's `organization_id` via
| TenantContext — never from a client-supplied id.
|
*/

// Public auth endpoints.
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Authenticated endpoints.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // TODO (next Sprint 1 requirement): tenant-scoped ticket + comment CRUD.
});
