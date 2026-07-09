<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->guardTestDatabase();
    }

    /**
     * Fail fast with an actionable message if the suite is about to run against
     * anything other than the in-memory SQLite test database.
     *
     * A stale config cache (bootstrap/cache/config.php, left by `php artisan
     * config:cache` or `optimize`) is loaded ahead of phpunit.xml's <env> vars,
     * so `php artisan test` silently points at the MySQL dev database — which
     * then errors intermittently or, worse, RefreshDatabase wipes real data.
     * `composer test` clears config first; running `php artisan test` directly
     * does not. This guard turns that confusing failure into one clear line.
     */
    private function guardTestDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            $this->fail(
                'Tests must run on the in-memory SQLite connection, but got '
                ."[{$connection}/{$database}]. A stale config cache is overriding "
                .'phpunit.xml — run `php artisan config:clear` (or use `composer test`).'
            );
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
