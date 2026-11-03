<?php

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAccessFixtures;
use Tests\TestCase;

/**
 * Characterisation tests: they record who can reach which API route TODAY
 * (31 Oct 2026, commit 7628a87 plus Batch 1a). They exist so that every later
 * RBAC batch can prove it changed only what was meant to change.
 *
 * The expected result for each role is computed from an independent model of
 * the current rules (permission map, role-name gates, Operations department
 * gate). A mismatch means either the code differs from that model or the
 * model is wrong. Either way it is a finding to report, never something to
 * "fix" by editing the expectation silently.
 *
 * Only route-level authorisation is checked: 401/403 means denied, any other
 * status (200, 404, 422 ...) means the request got past authorisation.
 * Server errors (5xx) are reported separately.
 *
 * KNOWN DEFECT (recorded 1 Nov 2026, approved handling): GET /payroll/settings is
 * excluded from the general table and covered by two dedicated tests at the end of
 * this class. See test_known_defect_payroll_settings_read_is_broken_by_route_shadowing().
 */
class CurrentAccessTest extends TestCase
{
    use RefreshDatabase, CreatesAccessFixtures;

    private const OPERATIONS_DEPARTMENT = 'operations';

    /** Role => permissions, as seeded by PermissionSeeder (verified against the live database on 31 Oct). */
    private const ROLE_PERMISSIONS = [
        'supervisor' => [
            'sites.view', 'guards.view', 'assignments.view', 'attendance.view', 'attendance.record',
            'roster.view', 'incidents.view', 'incidents.manage',
        ],
        'manager' => [
            'clients.view', 'contracts.view', 'sites.view', 'sites.manage', 'guards.view',
            'employees.view', 'departments.view', 'roster.view', 'roster.generate', 'incidents.view',
        ],
        'hr' => ['employees.view', 'employees.manage', 'departments.view'],
        'accountant' => ['payroll.view', 'payroll.manage'],
        'guard' => [],
        'client' => [],
    ];

    private const ALL_PERMISSIONS = [
        'clients.view', 'clients.manage', 'contracts.view', 'contracts.manage',
        'sites.view', 'sites.manage', 'guards.view', 'guards.manage',
        'assignments.view', 'assignments.manage', 'attendance.view', 'attendance.record',
        'roster.view', 'roster.generate', 'incidents.view', 'incidents.manage',
        'payroll.view', 'payroll.manage', 'employees.view', 'employees.manage',
        'departments.view', 'departments.manage', 'approvals.view_all',
    ];

    /** Role, department code. Mirrors the real accounts found in Batch 0. */
    public static function actors(): array
    {
        return [
            'admin (no department)'   => ['admin', null],
            'supervisor (operations)' => ['supervisor', self::OPERATIONS_DEPARTMENT],
            'manager (operations)'    => ['manager', self::OPERATIONS_DEPARTMENT],
            'manager (finance)'       => ['manager', 'finance'],
            'manager (no department)' => ['manager', null],
            'hr (hr department)'      => ['hr', 'hr'],
            'accountant (finance)'    => ['accountant', 'finance'],
            'guard (operations)'      => ['guard', self::OPERATIONS_DEPARTMENT],
            'client (no department)'  => ['client', null],
        ];
    }

    /**
     * [method, uri under /api/v1, required permissions (all), required roles (any), Operations department gate]
     * Ids are deliberately non-existent (999999) so no real record is touched.
     * Routes whose authorisation happens inside the controller (approval show/approve/reject/return/cancel)
     * are left out: this file covers route-level rules only.
     */
    private static function routes(): array
    {
        $ops = true;

        return [
            // Open to any logged-in user
            ['GET',    '/user',                                  [], [], false],
            ['GET',    '/notifications',                         [], [], false],
            ['GET',    '/notifications/unread',                  [], [], false],
            ['PATCH',  '/notifications/999999/read',             [], [], false],
            ['POST',   '/incidents',                             [], [], false],
            ['POST',   '/incidents/999999/attachments',          [], [], false],
            ['GET',    '/incident-types',                        [], [], false],
            ['POST',   '/approvals',                             [], [], false],
            ['GET',    '/approvals/pending',                     [], [], false],
            ['GET',    '/approvals/mine',                        [], [], false],
            ['GET',    '/money-requests',                        [], [], false],

            // Sites and posts
            ['GET',    '/sites',                                 ['sites.view'],   [], $ops],
            ['GET',    '/sites/999999',                          ['sites.view'],   [], $ops],
            ['POST',   '/sites',                                 ['sites.manage'], [], $ops],
            ['PUT',    '/sites/999999',                          ['sites.manage'], [], $ops],
            ['DELETE', '/sites/999999',                          ['sites.manage'], [], $ops],
            ['GET',    '/sites/999999/posts',                    ['sites.view'],   [], $ops],
            ['POST',   '/sites/999999/posts',                    ['sites.view', 'sites.manage'], [], $ops],
            ['GET',    '/posts/999999',                          ['sites.view'],   [], $ops],
            ['PUT',    '/posts/999999',                          ['sites.manage'], [], $ops],
            ['DELETE', '/posts/999999',                          ['sites.manage'], [], $ops],

            // Guards
            ['GET',    '/guards',                                ['guards.view'],   [], $ops],
            ['GET',    '/guards/999999',                         ['guards.view'],   [], $ops],
            ['POST',   '/guards',                                ['guards.manage'], [], $ops],
            ['PUT',    '/guards/999999',                         ['guards.manage'], [], $ops],
            ['DELETE', '/guards/999999',                         ['guards.manage'], [], $ops],

            // Assignments (the whole prefix needs assignments.view, writes also need assignments.manage)
            ['GET',    '/assignments',                           ['assignments.view'], [], $ops],
            ['POST',   '/assignments/assign',                    ['assignments.view', 'assignments.manage'], [], $ops],
            ['POST',   '/assignments/end/999999',                ['assignments.view', 'assignments.manage'], [], $ops],

            // Attendance
            ['POST',   '/attendance/check-in',                   ['attendance.record'], [], $ops],
            ['POST',   '/attendance/check-out',                  ['attendance.record'], [], $ops],
            ['GET',    '/attendance/today',                      ['attendance.view'],   [], $ops],
            ['GET',    '/attendance/guard/999999',               ['attendance.view'],   [], $ops],
            ['GET',    '/attendance/site/999999',                ['attendance.view'],   [], $ops],

            // Roster
            ['GET',    '/roster',                                ['roster.view'],     [], $ops],
            ['GET',    '/roster/2026-01-01',                     ['roster.view'],     [], $ops],
            ['POST',   '/roster/generate',                       ['roster.generate'], [], $ops],
            ['POST',   '/roster/assign-double',                  ['roster.generate'], [], $ops],
            ['DELETE', '/roster/999999',                         ['roster.generate'], [], $ops],

            // Dashboards (role names, no department gate)
            ['GET',    '/dashboard/live-shift',                  [], ['admin', 'supervisor', 'manager'], false],
            ['GET',    '/dashboard/admin',                       [], ['admin', 'supervisor', 'manager'], false],
            ['GET',    '/dashboard/supervisor',                  [], ['admin', 'supervisor', 'manager'], false],

            // Incidents
            ['GET',    '/incidents',                             ['incidents.view'],   [], $ops],
            ['GET',    '/incidents/summary',                     ['incidents.view'],   [], $ops],
            ['GET',    '/incidents/999999',                      ['incidents.view'],   [], $ops],
            ['POST',   '/incidents/999999/resolve',              ['incidents.manage'], [], $ops],
            ['PATCH',  '/incidents/999999/status',               ['incidents.manage'], [], $ops],
            ['POST',   '/incident-types',                        [], ['admin'], false],
            ['PUT',    '/incident-types/999999',                 [], ['admin'], false],

            // Approvals overview
            ['GET',    '/approvals',                             [], ['admin'], false],

            // Payroll
            ['GET',    '/payroll',                               ['payroll.view'],   [], false],
            ['GET',    '/payroll/deduction-types',               ['payroll.view'],   [], false],
            ['GET',    '/payroll/employer-cost',                 ['payroll.view'],   [], false],
            ['GET',    '/payroll/999999',                        ['payroll.view'],   [], false],
            ['POST',   '/payroll/generate',                      ['payroll.manage'], [], false],
            ['POST',   '/payroll/generate-bulk',                 ['payroll.manage'], [], false],
            ['POST',   '/payroll/deductions',                    ['payroll.manage'], [], false],
            ['POST',   '/payroll/deduction-types',               ['payroll.manage'], [], false],
            ['DELETE', '/payroll/deduction-types/999999',        ['payroll.manage'], [], false],
            ['PATCH',  '/payroll/999999/status',                 ['payroll.manage'], [], false],
            // GET /payroll/settings is deliberately NOT in this table: see knownDefectRoutes().
            ['PUT',    '/payroll/settings/some_key',             [], ['admin'], false],

            // Clients and contracts (admin only, interim)
            ['GET',    '/clients',                               [], ['admin'], false],
            ['GET',    '/clients/999999',                        [], ['admin'], false],
            ['POST',   '/clients',                               [], ['admin'], false],
            ['PUT',    '/clients/999999',                        [], ['admin'], false],
            ['DELETE', '/clients/999999',                        [], ['admin'], false],
            ['GET',    '/contracts',                             [], ['admin'], false],
            ['GET',    '/contracts/999999',                      [], ['admin'], false],
            ['POST',   '/contracts',                             [], ['admin'], false],
            ['PUT',    '/contracts/999999',                      [], ['admin'], false],
            ['DELETE', '/contracts/999999',                      [], ['admin'], false],

            // Employees
            ['GET',    '/employees',                             ['employees.view'],   [], false],
            ['GET',    '/employees/999999',                      ['employees.view'],   [], false],
            ['GET',    '/employees/999999/documents',            ['employees.view'],   [], false],
            ['POST',   '/employees',                             ['employees.manage'], [], false],
            ['PUT',    '/employees/999999',                      ['employees.manage'], [], false],
            ['DELETE', '/employees/999999',                      ['employees.manage'], [], false],
            ['POST',   '/employees/999999/documents',            ['employees.manage'], [], false],
            ['DELETE', '/employees/999999/documents/999999',     ['employees.manage'], [], false],
            ['PUT',    '/employees/999999/link-user',            [], ['admin'], false],

            // Users, departments, positions, documents
            ['GET',    '/users',                                 [], ['admin'], false],
            ['POST',   '/users',                                 [], ['admin'], false],
            ['PUT',    '/users/999999',                          [], ['admin'], false],
            ['GET',    '/departments',                           ['departments.view'],   [], false],
            ['GET',    '/departments/999999',                    ['departments.view'],   [], false],
            ['POST',   '/departments',                           ['departments.manage'], [], false],
            ['PUT',    '/departments/999999',                    ['departments.manage'], [], false],
            ['DELETE', '/departments/999999',                    ['departments.manage'], [], false],
            ['GET',    '/positions',                             ['employees.view'], [], false],
            ['POST',   '/positions',                             [], ['admin'], false],
            ['PUT',    '/positions/999999',                      [], ['admin'], false],
            ['DELETE', '/positions/999999',                      [], ['admin'], false],
            ['GET',    '/documents',                             [], ['admin', 'supervisor'], false],
            ['POST',   '/documents',                             [], ['admin', 'supervisor'], false],
            ['DELETE', '/documents/999999',                      [], ['admin', 'supervisor'], false],

            // Money requests (create)
            ['POST',   '/money-requests',                        [], ['admin', 'manager'], false],

            // Admin test route
            ['GET',    '/admin-test',                            [], ['admin'], false],
        ];
    }

    /**
     * Routes whose current behaviour is a known defect. They are still checked for
     * "rejected without a token" but have their own tests below.
     */
    private static function knownDefectRoutes(): array
    {
        return [
            // Declared role:admin in routes/api.php, but GET /payroll/{id} is registered first.
            ['GET', '/payroll/settings', [], ['admin'], false],
        ];
    }

    private function permissionsFor(string $role): array
    {
        return $role === 'admin' ? self::ALL_PERMISSIONS : (self::ROLE_PERMISSIONS[$role] ?? []);
    }

    private function expectedAllowed(string $role, ?string $department, array $permissions, array $roles, bool $operationsGate): bool
    {
        $held = $this->permissionsFor($role);

        foreach ($permissions as $permission) {
            if (!in_array($permission, $held, true)) {
                return false;
            }
        }

        if ($roles !== [] && !in_array($role, $roles, true)) {
            return false;
        }

        if ($operationsGate) {
            // Current rule: admin, hr and users with no department bypass the gate.
            $bypass = in_array($role, ['admin', 'hr'], true) || $department === null;

            if (!$bypass && $department !== self::OPERATIONS_DEPARTMENT) {
                return false;
            }
        }

        return true;
    }

    #[DataProvider('actors')]
    public function test_route_access_matches_current_behaviour(string $role, ?string $department): void
    {
        $this->seedAccessFixtures();
        Sanctum::actingAs($this->makeUser($role, $department), ['*']);

        $mismatches = [];
        $serverErrors = [];

        foreach (self::routes() as [$method, $uri, $permissions, $roles, $operationsGate]) {
            $expected = $this->expectedAllowed($role, $department, $permissions, $roles, $operationsGate);
            $status = $this->json($method, '/api/v1' . $uri, [])->getStatusCode();
            $allowed = !in_array($status, [401, 403], true);

            if ($allowed !== $expected) {
                $mismatches[] = sprintf(
                    '%-7s %-42s expected %-7s but got HTTP %d',
                    $method,
                    $uri,
                    $expected ? 'ALLOWED' : 'DENIED',
                    $status
                );
            }

            if ($status >= 500) {
                $serverErrors[] = sprintf('%-7s %-42s HTTP %d', $method, $uri, $status);
            }
        }

        if ($mismatches !== [] || $serverErrors !== []) {
            $message = sprintf("\nActor: %s%s\n", $role, $department ? " ({$department})" : ' (no department)');

            if ($mismatches !== []) {
                $message .= "\nACCESS MISMATCHES:\n  " . implode("\n  ", $mismatches) . "\n";
            }

            if ($serverErrors !== []) {
                $message .= "\nSERVER ERRORS (HTTP 5xx):\n  " . implode("\n  ", $serverErrors) . "\n";
            }

            $this->fail($message);
        }

        $this->addToAssertionCount(1);
    }

    public function test_unauthenticated_requests_are_rejected_on_every_route(): void
    {
        $notRejected = [];

        foreach (array_merge(self::routes(), self::knownDefectRoutes()) as [$method, $uri]) {
            $status = $this->json($method, '/api/v1' . $uri, [])->getStatusCode();

            if ($status !== 401) {
                $notRejected[] = sprintf('%-7s %-42s HTTP %d (expected 401)', $method, $uri, $status);
            }
        }

        $this->assertSame([], $notRejected, "\nRoutes reachable without a token:\n  " . implode("\n  ", $notRejected));
    }

    /**
     * Flip to true in the SAME commit that fixes the route (for example by adding
     * ->whereNumber('id') to GET /payroll/{id}). That enables the intended-behaviour
     * test below and should be accompanied by deleting the known-defect test.
     */
    private const PAYROLL_SETTINGS_ROUTE_FIXED = false;

    /**
     * TEMPORARY REGRESSION TEST FOR A KNOWN DEFECT. HTTP 500 is NOT acceptable
     * application behaviour; this test only pins the defect so that it cannot change
     * unnoticed while the route fix waits for separate approval.
     *
     * Cause: GET /payroll/{id} (payroll.view group) is registered before
     * GET /payroll/settings (role:admin group). Laravel matches {id} first and
     * PayrollController::show(int $id) receives the text "settings" -> server error.
     *
     * Intended behaviour (see the next test): admin gets a successful response,
     * every other role gets 403.
     */
    public function test_known_defect_payroll_settings_read_is_broken_by_route_shadowing(): void
    {
        $this->seedAccessFixtures();

        $observed = [];

        foreach ([['admin', null], ['accountant', 'finance'], ['supervisor', self::OPERATIONS_DEPARTMENT]] as [$role, $department]) {
            Sanctum::actingAs($this->makeUser($role, $department), ['*']);
            $observed[$role] = $this->json('GET', '/api/v1/payroll/settings', [])->getStatusCode();
        }

        $this->assertSame(
            ['admin' => 500, 'accountant' => 500, 'supervisor' => 403],
            $observed,
            "GET /payroll/settings no longer behaves as the recorded known defect.\n"
            . "If the route was fixed on purpose, set PAYROLL_SETTINGS_ROUTE_FIXED to true and delete this test.\n"
            . "Otherwise investigate: observed " . json_encode($observed)
        );
    }

    /**
     * The behaviour the route is meant to have. Skipped until the route is fixed.
     */
    public function test_intended_payroll_settings_read_is_admin_only(): void
    {
        if (!self::PAYROLL_SETTINGS_ROUTE_FIXED) {
            $this->markTestSkipped('Known defect: GET /payroll/settings is shadowed by GET /payroll/{id}. Enable after the route fix.');
        }

        $this->seedAccessFixtures();

        foreach ([['admin', null, 200], ['accountant', 'finance', 403], ['supervisor', self::OPERATIONS_DEPARTMENT, 403]] as [$role, $department, $expected]) {
            Sanctum::actingAs($this->makeUser($role, $department), ['*']);

            $this->json('GET', '/api/v1/payroll/settings', [])->assertStatus($expected);
        }
    }
}
