<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'pulsedesk')->firstOrFail();
        TenantContext::set($organization->id);

        $policies = [
            ['priority' => 'urgent', 'response_hours' => 1, 'resolution_hours' => 4],
            ['priority' => 'high', 'response_hours' => 2, 'resolution_hours' => 8],
            ['priority' => 'medium', 'response_hours' => 4, 'resolution_hours' => 24],
            ['priority' => 'low', 'response_hours' => 8, 'resolution_hours' => 72],
        ];

        foreach ($policies as $policy) {
            SlaPolicy::updateOrCreate(
                ['priority' => $policy['priority']],
                $policy
            );
        }
    }
}
