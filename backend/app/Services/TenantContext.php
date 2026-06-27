<?php

namespace App\Services;

/**
 * Resolves the "current tenant" (organization id) for the multi-tenancy
 * global scope.
 *
 * The active organization id can be set explicitly (e.g. by auth middleware,
 * seeders or tests). When nothing is set explicitly it falls back to the
 * authenticated user's organization_id, guarded against recursion.
 */
class TenantContext
{
    protected static ?int $organizationId = null;
    protected static bool $resolving = false;

    public static function set(?int $organizationId): void
    {
        static::$organizationId = $organizationId;
    }

    public static function get(): ?int
    {
        if (! is_null(static::$organizationId)) {
            return static::$organizationId;
        }

        // Avoid infinite recursion: resolving the auth user may itself trigger
        // a scoped query which calls back into get().
        if (static::$resolving) {
            return null;
        }

        static::$resolving = true;
        try {
            $user = auth()->user();
        } finally {
            static::$resolving = false;
        }

        return $user?->organization_id;
    }

    public static function clear(): void
    {
        static::$organizationId = null;
    }
}
