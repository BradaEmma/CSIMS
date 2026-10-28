<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Approved employee positions and the department each belongs to.
 * Safe to run repeatedly (updateOrCreate by name). To approve a new position,
 * add a line below and run: php artisan db:seed --class=PositionSeeder
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        // position name => department code
        $positions = [
            'Guard'              => 'operations',
            'Guard Supervisor'   => 'operations',
            'Sale'               => 'sales',
            'Technician'         => 'technical',
            'Chief Accountant'   => 'finance',
            'Managing Director'  => 'hr',
            'Director & Founder' => 'hr',
        ];

        foreach ($positions as $name => $departmentCode) {
            $department = Department::where('code', $departmentCode)->first();

            if (!$department) {
                throw new \RuntimeException("Department with code '{$departmentCode}' not found for position '{$name}'.");
            }

            Position::updateOrCreate(['name' => $name], ['department_id' => $department->id]);
        }
    }
}