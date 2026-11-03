<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    /**
     * The guard runs BEFORE the framework boots and before RefreshDatabase
     * can migrate or wipe anything.
     */
    protected function setUp(): void
    {
        TestDatabaseGuard::assertSafe($this->configuredDatabaseName());

        parent::setUp();
    }

    private function configuredDatabaseName(): ?string
    {
        $value = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
