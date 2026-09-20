<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalWorkflow;

class MoneyRequestWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = ApprovalWorkflow::firstOrCreate(
            ['module' => 'money_request'],
            ['name' => 'Money Request Approval', 'is_active' => true]
        );

        // Level 1 — Accountant. Unbounded range so it's always the entry
        // point regardless of amount: every request goes through the
        // Accountant first, per the stated business rule.
        $workflow->levels()->updateOrCreate(
            ['sequence' => 1],
            [
                'name' => 'Accountant Review',
                'approver_role' => 'accountant',
                'min_amount' => null,
                'max_amount' => null,
            ]
        );

        // Level 2 — Admin/GM final approval, only for amounts > 300,000.
        // NOTE: min_amount is 300000.01, not 300000. The engine's level
        // matching uses an INCLUSIVE >= check, so a literal 300000 here
        // would incorrectly also catch requests of exactly 300,000 —
        // which the business rule says should stop at Accountant only.
        // The 0.01 offset is what makes the threshold strictly "greater
        // than 300,000" using the engine's existing inclusive semantics,
        // without changing the engine itself.
        $workflow->levels()->updateOrCreate(
            ['sequence' => 2],
            [
                'name' => 'Admin / GM Final Approval',
                'approver_role' => 'admin',
                'min_amount' => 300000.01,
                'max_amount' => null,
            ]
        );
    }
}