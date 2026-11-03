<?php

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HarnessSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tests_run_against_the_dedicated_mysql_test_database(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('csims_test', DB::connection()->getDatabaseName());
    }
}
