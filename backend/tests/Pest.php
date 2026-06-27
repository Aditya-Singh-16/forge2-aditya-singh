<?php

use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is bound to the base
| TestCase. Feature tests get a fresh database every time.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Hooks
|--------------------------------------------------------------------------
*/

// Ensure the tenant context never leaks between tests.
afterEach(function () {
    TenantContext::clear();
});
