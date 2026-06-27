<?php

namespace App\Traits;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adds multi-tenancy to a model:
 *   1. A global scope that limits queries to the current organization.
 *   2. Auto-fills organization_id on create from the active TenantContext.
 *   3. An organization() relationship.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope());

        static::creating(function (Model $model) {
            if (is_null($model->getAttribute('organization_id'))) {
                $model->setAttribute('organization_id', TenantContext::get());
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Fully-qualified organization_id column (table.organization_id), used by
     * the global scope to avoid ambiguity across joins.
     */
    public function getQualifiedOrganizationIdColumn(): string
    {
        return $this->getTable().'.organization_id';
    }
}
