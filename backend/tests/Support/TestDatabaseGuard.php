<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Refuses to let the test suite run against any database except the
 * dedicated test database. RefreshDatabase drops every table, so running
 * the suite against the real `csims` database would destroy live data.
 */
final class TestDatabaseGuard
{
    public const ALLOWED_DATABASE = 'csims_test';

    public static function assertSafe(?string $database): void
    {
        if ($database !== self::ALLOWED_DATABASE) {
            throw new RuntimeException(sprintf(
                "Refusing to run tests: the configured database is '%s' but only '%s' is allowed. "
                . "Check the DB_DATABASE value in phpunit.xml and your shell environment.",
                $database ?? '(not set)',
                self::ALLOWED_DATABASE
            ));
        }
    }
}
