<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialises the stancl/tenancy context from the authenticated user's
 * tenant_id.  Designed for the central-domain routes in web.php (dev
 * access + testing); on real tenant domains, InitializeTenancyByDomain
 * runs instead and this middleware becomes a no-op.
 */
class InitializeTenancyFromAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenancy()->initialized && $request->user()?->tenant_id) {
            /** @var Tenant|null $tenant */
            $tenant = Tenant::withoutTenantScope()
                ->find($request->user()->tenant_id);

            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        return $next($request);
    }
}
