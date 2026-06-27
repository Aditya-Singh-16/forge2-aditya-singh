<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'pulsedesk')->firstOrFail();
        TenantContext::set($organization->id);

        $agents = User::where('role', 'agent')->get();
        $customers = User::where('role', 'customer')->get();

        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        // 12 tickets with varied statuses and priorities.
        $tickets = [
            ['subject' => 'Cannot log in to my account', 'description' => 'I keep getting a 401 after entering my credentials. Tried resetting the password with no luck.'],
            ['subject' => 'Invoice shows wrong amount', 'description' => 'My latest invoice is charging me twice for the Pro plan.'],
            ['subject' => 'Feature request: dark mode', 'description' => 'It would be great to have a dark theme across the dashboard.'],
            ['subject' => 'API returns 500 on /tickets', 'description' => 'Intermittent 500 errors when listing tickets via the REST API.'],
            ['subject' => 'Email notifications not arriving', 'description' => 'I have not received any ticket update emails this week.'],
            ['subject' => 'How do I export my data?', 'description' => 'Is there a way to export all ticket history to CSV?'],
            ['subject' => 'Mobile app crashes on launch', 'description' => 'The iOS app crashes immediately after the splash screen.'],
            ['subject' => 'SSO setup with Okta', 'description' => 'Need help configuring SAML SSO using our Okta tenant.'],
            ['subject' => 'Slow dashboard loading', 'description' => 'The dashboard takes ~20s to load during peak hours.'],
            ['subject' => 'Cannot assign tickets to agent', 'description' => 'The assignee dropdown is empty for some users.'],
            ['subject' => 'Two-factor auth locked me out', 'description' => 'Lost my authenticator device and cannot disable 2FA.'],
            ['subject' => 'Request data deletion (GDPR)', 'description' => 'Please delete all data associated with my account per GDPR.'],
        ];

        $tagSets = [['billing'], ['bug'], ['feature-request'], ['api'], ['email'], ['how-to'], ['mobile'], ['sso'], ['performance'], ['bug'], ['security'], ['gdpr']];

        foreach ($tickets as $i => $ticket) {
            Ticket::updateOrCreate(
                ['subject' => $ticket['subject']],
                [
                    'description' => $ticket['description'],
                    'status' => $statuses[$i % count($statuses)],
                    'priority' => $priorities[$i % count($priorities)],
                    'requester_id' => $customers[$i % $customers->count()]->id,
                    'assignee_id' => $i % 3 !== 0 ? $agents[$i % $agents->count()]->id : null,
                    'tags' => $tagSets[$i],
                ]
            );
        }
    }
}
