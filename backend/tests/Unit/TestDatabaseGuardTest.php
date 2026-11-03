<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

/**
 * Pure logic test: no Laravel, no database connection. It proves the guard
 * rejects the real `csims` database without ever connecting to it.
 */
class TestDatabaseGuardTest extends TestCase
{
    public static function refusedDatabases(): array
    {
        return [
            'the real database'     => ['csims'],
            'a lookalike'           => ['csims_test2'],
            'a different case'      => ['CSIMS'],
            'an in-memory database' => [':memory:'],
            'empty string'          => [''],
            'not set'               => [null],
        ];
    }

    #[DataProvider('refusedDatabases')]
    public function test_refuses_every_database_except_the_test_database(?string $name): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabaseGuard::assertSafe($name);
    }

    public function test_allows_the_dedicated_test_database(): void
    {
        TestDatabaseGuard::assertSafe('csims_test');

        $this->addToAssertionCount(1);
    }
}
