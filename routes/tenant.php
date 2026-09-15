<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
| Single-database, shared-schema tenancy: all application routes live in
| web.php and are protected by the InitializeTenancyFromAuth middleware,
| which reads the authenticated user's tenant_id to initialise the tenant
| context.  No route duplication is needed here.
|
| This file is intentionally empty for now.  It exists as a hook for any
| future domain-specific routing (e.g. a custom tenant login subdomain)
| that cannot be expressed in web.php.
|
| Why not InitializeTenancyByDomain?
| - Our bootstrappers list is empty (no Redis/filesystem/queue coupling).
| - Single-DB isolation is enforced purely at the Eloquent layer.
| - Domain-based init would break tests (localhost has no matching tenant).
|--------------------------------------------------------------------------
*/
