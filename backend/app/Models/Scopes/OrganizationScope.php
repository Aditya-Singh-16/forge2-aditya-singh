<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Automatically scopes every query to the current tenant's organization_id.
 * When no tenant is resolved (e.g. during seeding or an unauthenticated
 * request) no filter is applied.
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = TenantContext::get();

        if (! is_null($organizationId)) {
            $column = method_exists($model, 'getQualifiedOrganizationIdColumn')
                ? $model->getQualifiedOrganizationIdColumn()
                : $model->getTable().'.organization_id';

            $builder->where($column, '=', $organizationId);
        }
    }
}
