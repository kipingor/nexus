<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Tenant;

return [
    'tenant_model' => Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,
    'domain_model' => Domain::class,

    /**
     * Central domains: requests to these domains are handled by the central
     * app (platform admin, sign-up flow). Tenant routes are on subdomains
     * or custom domains.
     *
     * In Replit dev, the app runs on localhost. Override via CENTRAL_DOMAIN env.
     */
    'central_domains' => array_filter(explode(',', env('CENTRAL_DOMAINS', '127.0.0.1,localhost'))),

    /**
     * Bootstrappers run when a tenant request is initialised.
     *
     * DatabaseTenancyBootstrapper is intentionally absent: we use a single
     * shared database. Tenant isolation is handled by the BelongsToTenant
     * global Eloquent scope on every tenant-owned model.
     *
     * If a future customer requires hard DB isolation, stancl/tenancy
     * supports adding DatabaseTenancyBootstrapper without any other rewrites
     * (tenant scoping stays at the model/query layer).
     */
    'bootstrappers' => [
        // All bootstrappers omitted for single-DB shared-schema tenancy.
        //
        // Rationale: tenant isolation is enforced entirely at the Eloquent layer
        // via the BelongsToTenant global scope — no per-tenant Redis/filesystem/
        // queue namespace is needed.  Keeping this list empty also avoids the
        // phpredis PHP extension requirement (we use predis/predis instead).
        //
        // Add back selectively if per-tenant cache/filesystem/queue isolation
        // is required in a future milestone.
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'pgsql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant',
        'suffix' => '',
        'managers' => [
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [],
        'root_override' => [],
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        // Stancl\Tenancy\Features\TenantConfig::class,
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];
