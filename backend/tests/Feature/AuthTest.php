<?php

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;

beforeEach(function () {
    // The register tests do not require a pre-existing org (register
    // self-creates it); login/logout tests build one explicitly below.
});

describe('Registration', function () {
    it('registers a new user and returns a Sanctum token', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'password123',
            'organization_slug' => 'acme',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email'], 'token']);

        expect($response->json('token'))->toBeString()
            ->and($response->json('user.email'))->toBe('jane@example.test');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.test',
            'role' => 'customer',
        ]);
    });

    it('creates the organization when it does not yet exist', function () {
        $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'password123',
            'organization_slug' => 'brand-new-co',
        ]);

        $this->assertDatabaseHas('organizations', ['slug' => 'brand-new-co']);
    });

    it('joins an existing organization instead of duplicating it', function () {
        Organization::create(['name' => 'Acme', 'slug' => 'acme']);

        $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'password123',
            'organization_slug' => 'acme',
        ]);

        expect(Organization::where('slug', 'acme')->count())->toBe(1);
    });

    it('rejects a duplicate email', function () {
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme']);
        TenantContext::set($org->id);

        User::create([
            'name' => 'Existing',
            'email' => 'taken@example.test',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'taken@example.test',
            'password' => 'password123',
            'organization_slug' => 'acme',
        ])->assertUnprocessable();
    });

    it('validates required fields', function () {
        $this->postJson('/api/register', [])->assertUnprocessable();
    });
});

describe('Login', function () {
    beforeEach(function () {
        $this->org = Organization::create(['name' => 'Acme', 'slug' => 'acme']);
        TenantContext::set($this->org->id);

        $this->user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.test',
            'password' => 'password123',
            'role' => 'customer',
        ]);
    });

    it('logs in with valid credentials and returns a token', function () {
        $response = $this->postJson('/api/login', [
            'email' => 'john@example.test',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'user' => ['id', 'email'], 'token']);

        expect($response->json('token'))->toBeString();
    });

    it('rejects an invalid password', function () {
        $this->postJson('/api/login', [
            'email' => 'john@example.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    });

    it('rejects an unknown email', function () {
        $this->postJson('/api/login', [
            'email' => 'nobody@example.test',
            'password' => 'password123',
        ])->assertUnprocessable();
    });
});

describe('Logout', function () {
    beforeEach(function () {
        $this->org = Organization::create(['name' => 'Acme', 'slug' => 'acme']);
        TenantContext::set($this->org->id);

        $this->user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.test',
            'password' => 'password123',
            'role' => 'customer',
        ]);
    });

    it('logs out and revokes the current token', function () {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);

        // Revocation proof: the access-token row is removed from storage.
        // (Verifying via a second HTTP request would be confounded by Sanctum's
        // stateful session fallback in the test env, where 127.0.0.1 is treated
        // as a first-party SPA origin. The token itself is gone from the DB.)
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
        ]);
    });

    it('rejects unauthenticated access', function () {
        $this->postJson('/api/logout')->assertUnauthorized();
    });
});
