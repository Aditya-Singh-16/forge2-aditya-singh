<?php

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TenantContext;

beforeEach(function () {
    // Organization A
    $this->orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a']);
    TenantContext::set($this->orgA->id);
    $this->userA = User::create([
        'name' => 'User A',
        'email' => 'a@org-a.test',
        'role' => 'customer',
        'password' => 'password',
    ]);
    $this->ticketA = Ticket::create([
        'subject' => 'Org A ticket',
        'requester_id' => $this->userA->id,
    ]);

    // Organization B
    $this->orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b']);
    TenantContext::set($this->orgB->id);
    $this->userB = User::create([
        'name' => 'User B',
        'email' => 'b@org-b.test',
        'role' => 'customer',
        'password' => 'password',
    ]);
    $this->ticketB = Ticket::create([
        'subject' => 'Org B ticket',
        'requester_id' => $this->userB->id,
    ]);
});

describe('Cross-tenant isolation', function () {
    it('scopes tickets to the current organization', function () {
        TenantContext::set($this->orgA->id);
        expect(Ticket::count())->toBe(1)
            ->and(Ticket::first()->subject)->toBe('Org A ticket');

        TenantContext::set($this->orgB->id);
        expect(Ticket::count())->toBe(1)
            ->and(Ticket::first()->subject)->toBe('Org B ticket');
    });

    it('cannot read another organization ticket by id', function () {
        TenantContext::set($this->orgA->id);

        // Org A must not be able to fetch Org B's ticket directly.
        expect(Ticket::find($this->ticketB->id))->toBeNull();
    });

    it('scopes users per organization', function () {
        TenantContext::set($this->orgA->id);
        expect(User::count())->toBe(1)
            ->and(User::where('email', 'b@org-b.test')->exists())->toBeFalse();

        TenantContext::set($this->orgB->id);
        expect(User::where('email', 'a@org-a.test')->exists())->toBeFalse();
    });

    it('returns no tickets for a foreign/unknown organization', function () {
        TenantContext::set(999999);

        expect(Ticket::count())->toBe(0);
    });

    it('can still bypass the scope when explicitly needed (e.g. super-admin)', function () {
        TenantContext::set($this->orgA->id);

        // Without the global scope, both organizations' tickets are visible.
        $all = Ticket::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->count();

        expect($all)->toBe(2);
    });
});
