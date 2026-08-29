<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'clients.view', 'clients.manage',
            'contracts.view', 'contracts.manage',
            'sites.view', 'sites.manage',
            'guards.view', 'guards.manage',
            'assignments.view', 'assignments.manage',
            'attendance.view', 'attendance.record',
            'roster.view', 'roster.generate',
            'incidents.view', 'incidents.manage',
            'payroll.view', 'payroll.manage',
            'employees.view', 'employees.manage',
            'departments.view', 'departments.manage',
            'approvals.view_all',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $rolePermissions = [
            'admin' => $permissions, // all

            'supervisor' => [
                'sites.view',
                'guards.view',
                'assignments.view',
                'attendance.view', 'attendance.record',
                'roster.view',
                'incidents.view', 'incidents.manage',
            ],

            'manager' => [
                // department-scoped access is enforced by middleware,
                // not by narrowing this list further
                'clients.view', 'contracts.view',
                'sites.view', 'guards.view',
                'employees.view',
                'departments.view',
                'roster.view',
            ],

            'hr' => [
                'employees.view', 'employees.manage',
                'departments.view',
            ],

            'accountant' => [
                'payroll.view', 'payroll.manage',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissionNames);
        }
    }
}