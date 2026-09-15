<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

/**
 * Tenancy configuration.
 *
 * Architecture: single database, shared schema.
 * - DatabaseTenancyBootstrapper is intentionally omitted — we do NOT create
 *   a separate database per tenant. Tenant isolation is enforced at the query
 *   layer via the BelongsToTenant global Eloquent scope.
 * - Cache and filesystem are still tenant-scoped so cached data and uploaded
 *   files never bleed across tenant boundaries.
 */
class TenancyServiceProvider extends ServiceProvider
{
    public static string $controllerNamespace = '';

    public function events(): array
    {
        return [
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class  => [
                // Single-DB: no DB creation job needed.
                // Seed default roles/permissions instead.
                JobPipeline::make([
                    \App\Jobs\SeedTenantDefaults::class,
                ])->send(fn (Events\TenantCreated $event) => $event->tenant)
                  ->shouldBeQueued(false),
            ],
            Events\SavingTenant::class   => [],
            Events\TenantSaved::class    => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class  => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class  => [],

            Events\DomainCreated::class  => [],
            Events\DomainSaved::class    => [],
            Events\DomainDeleted::class  => [],

            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class  => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class    => [],
            Events\TenancyEnded::class     => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class    => [],
            Events\TenancyBootstrapped::class     => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class  => [],
        ];
    }

    public function register(): void {}

    public function boot(): void
    {
        $this->bootEvents();
        $this->mapRoutes();
        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function bootEvents(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }
                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes(): void
    {
        $this->app->booted(function (): void {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $tenancyMiddleware = [
            Middleware\PreventAccessFromCentralDomains::class,
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]
                ->prependToMiddlewarePriority($middleware);
        }
    }
}
