<?php

namespace Tests\Concerns;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

trait CreatesAccessFixtures
{
    /**
     * Seeds the roles and permissions exactly as the application seeders
     * define them, plus the five CUBS departments.
     */
    protected function seedAccessFixtures(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $departments = [
            'technical'  => 'Technical Security Systems',
            'hr'         => 'Administration & Compliance',
            'sales'      => 'Sales & Business Development',
            'operations' => 'Operations & Security',
            'finance'    => 'Finance & Accounts',
        ];

        foreach ($departments as $code => $name) {
            Department::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }
    }

    /**
     * Creates a user holding exactly one role, in the given department
     * (null = no department).
     */
    protected function makeUser(string $role, ?string $departmentCode): User
    {
        $departmentId = $departmentCode === null
            ? null
            : Department::where('code', $departmentCode)->value('id');

        $user = User::factory()->create(['department_id' => $departmentId]);
        $user->assignRole($role);

        return $user;
    }
}
