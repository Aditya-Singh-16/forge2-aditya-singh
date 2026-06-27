<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'pulsedesk')->firstOrFail();

        // Seeders run unauthenticated: scope all tenant reads/writes explicitly.
        TenantContext::set($organization->id);

        // 1 admin
        User::updateOrCreate(
            ['email' => 'admin@pulsedesk.test'],
            ['name' => 'Ada Admin', 'role' => 'admin', 'password' => 'password']
        );

        // 2 agents
        $agents = [
            ['Morgan Agent', 'agent1@pulsedesk.test'],
            ['Riley Agent', 'agent2@pulsedesk.test'],
        ];
        foreach ($agents as [$name, $email]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'role' => 'agent', 'password' => 'password']
            );
        }

        // 2 customers
        $customers = [
            ['Casey Customer', 'customer1@pulsedesk.test'],
            ['Jordan Customer', 'customer2@pulsedesk.test'],
        ];
        foreach ($customers as [$name, $email]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'role' => 'customer', 'password' => 'password']
            );
        }
    }
}
