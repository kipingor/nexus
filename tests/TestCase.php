<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Override createApplication so test-safe config values are baked in
     * from the very start of the application lifecycle.
     *
     * WHY putenv() BEFORE parent::createApplication():
     * - start.sh writes .env with CACHE_STORE=redis, SESSION_DRIVER=redis.
     * - When `php artisan test` starts, artisan bootstraps once with .env,
     *   loading these values into $_ENV / getenv().
     * - phpdotenv uses createImmutable() — it NEVER overrides vars already
     *   in the environment, so .env.testing cannot fix them.
     * - Service providers (e.g. spatie/laravel-permission) may touch the
     *   cache during boot (inside parent::createApplication()), BEFORE any
     *   Config::set() call could take effect.
     * - putenv() + $_ENV assignment runs first, so config/*.php build with
     *   the safe test values when providers boot.
     * - The duplicate config([...]) below catches any service that reads the
     *   config repository directly after booting.
     */
    public function createApplication(): Application
    {
        // Force env vars before the application reads them during bootstrap.
        foreach ([
            'CACHE_STORE'      => 'array',
            'SESSION_DRIVER'   => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'REDIS_CLIENT'     => 'predis',
            'MAIL_MAILER'      => 'array',
        ] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }

        $app = parent::createApplication();

        // Belt-and-suspenders: override the config repository too, in case
        // any provider cached it from a previous app instance in this process.
        config([
            'cache.default'                      => 'array',
            'cache.stores.array'                 => ['driver' => 'array', 'serialize' => false],
            'session.driver'                     => 'array',
            'queue.default'                      => 'sync',
            'database.redis.client'              => 'predis',
            'inertia.testing.ensure_pages_exist' => false,
        ]);

        return $app;
    }
}
