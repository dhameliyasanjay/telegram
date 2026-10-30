<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        @unlink(__DIR__ . '/../bootstrap/cache/config.php');
        @unlink(__DIR__ . '/../bootstrap/cache/routes-v7.php');

        $app = parent::createApplication();
        $app['env'] = 'testing';

        // SAFETY LOCK: Always enforce in-memory SQLite and testing env for all tests
        config([
            'app.env'                              => 'testing',
            'database.default'                     => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver'                       => 'array',
            'cache.default'                        => 'array',
        ]);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Hard assertion: Tests must NEVER touch MySQL or any non-sqlite connection
        if (config('database.default') !== 'sqlite') {
            throw new \RuntimeException('SAFETY LOCK: Tests must run only on in-memory SQLite!');
        }

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }
}
