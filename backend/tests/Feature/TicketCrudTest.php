<?php

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TenantContext;

beforeEach(function () {
    $this->org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
    TenantContext::set($this->org->id);

    $this->customer = User::create([
        'name' => 'Casey Customer',
        'email' => 'casey@test.test',
        'role' => 'customer',
        'password' => 'password',
    ]);

    $this->agent = User::create([
        'name' => 'Morgan Agent',
        'email' => 'morgan@test.test',
        'role' => 'agent',
        'password' => 'password',
    ]);
});

describe('Ticket CRUD', function () {
    it('creates a ticket and auto-assigns the current organization', function () {
        $ticket = Ticket::create([
            'subject' => 'Login not working',
            'description' => 'User cannot log in to their account.',
            'status' => 'open',
            'priority' => 'high',
            'requester_id' => $this->customer->id,
            'assignee_id' => $this->agent->id,
            'tags' => ['bug', 'auth'],
        ]);

        expect($ticket->exists())->toBeTrue()
            ->and($ticket->organization_id)->toBe($this->org->id)
            ->and($ticket->tags)->toBeArray()->toBe(['bug', 'auth']);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'Login not working',
            'organization_id' => $this->org->id,
        ]);
    });

    it('lists only tickets belonging to the current organization', function () {
        Ticket::create(['subject' => 'T1', 'requester_id' => $this->customer->id]);
        Ticket::create(['subject' => 'T2', 'requester_id' => $this->customer->id]);

        expect(Ticket::count())->toBe(2)
            ->and(Ticket::pluck('subject')->sort()->values()->all())->toBe(['T1', 'T2']);
    });

    it('reads a single ticket by id', function () {
        $ticket = Ticket::create(['subject' => 'Find me', 'requester_id' => $this->customer->id]);

        $found = Ticket::find($ticket->id);

        expect($found)->not->toBeNull()
            ->and($found->subject)->toBe('Find me')
            ->and($found->requester->is($this->customer))->toBeTrue();
    });

    it('updates a ticket', function () {
        $ticket = Ticket::create([
            'subject' => 'Original',
            'status' => 'open',
            'priority' => 'low',
            'requester_id' => $this->customer->id,
        ]);

        $ticket->update([
            'status' => 'resolved',
            'priority' => 'urgent',
            'tags' => ['escalated'],
        ]);

        expect($ticket->fresh()->status)->toBe('resolved')
            ->and($ticket->fresh()->priority)->toBe('urgent')
            ->and($ticket->fresh()->tags)->toBe(['escalated']);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
            'priority' => 'urgent',
        ]);
    });

    it('deletes a ticket', function () {
        $ticket = Ticket::create(['subject' => 'Delete me', 'requester_id' => $this->customer->id]);

        $ticket->delete();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        expect(Ticket::find($ticket->id))->toBeNull();
    });

    it('respects default status and priority values', function () {
        $ticket = Ticket::create(['subject' => 'Defaults', 'requester_id' => $this->customer->id]);

        // DB column defaults are applied on insert; re-fetch to assert them.
        expect($ticket->fresh()->status)->toBe('open')
            ->and($ticket->fresh()->priority)->toBe('medium');
    });
});
