<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayrollSetting;
use App\Models\PayrollTaxBracket;

class PayrollSettingsSeeder extends Seeder
{
    /**
     * Tanzania Mainland private-sector statutory payroll settings.
     *
     * Source: TRA PAYE monthly resident bands; NSSF 10% employee +
     * 10% employer; SDL 3.5% employer (companies with 10+ employees);
     * WCF 0.5% employer (private sector).
     *
     * UNIT CONVENTION: every rate below is stored as a whole percentage
     * (e.g. "10" for 10%), never a decimal fraction (never "0.10").
     * This matches how PayrollService already consumes these values —
     * every existing rate lookup in the codebase divides by 100 at the
     * point of use (see calculatePaye() and the NSSF deduction line in
     * generatePayrollForGuard()). Storing a fraction here would silently
     * produce deductions 100x too small. Keep every future rate setting
     * on this same convention.
     *
     * PAYE is calculated on (gross - employee NSSF), not raw gross —
     * see PayrollService::generatePayrollForGuard(), which already does
     * this correctly. SDL and WCF are employer-side costs and must never
     * appear in the employee net-pay calculation — nothing in
     * PayrollService currently reads them, by design; they exist here
     * as configured values for whenever employer-cost reporting is built.
     *
     * NHIF is intentionally NOT seeded — not currently a mandatory
     * scheme for this product. Add it only if/when a voluntary-scheme
     * flag is actually needed.
     */
    public function run(): void
    {
        $settings = [
            // Employee / shared statutory — used in net pay today
            [
                'key' => 'nssf_employee_rate',
                'value' => '10',
                'description' => 'NSSF employee contribution rate (%), deducted from gross pay. Already read by PayrollService.',
            ],
            [
                'key' => 'nssf_employer_rate',
                'value' => '10',
                'description' => 'NSSF employer contribution rate (%) — company cost, never deducted from employee net pay. Not yet read by any code path; seeded for future employer-cost reporting.',
            ],
            [
                'key' => 'nssf_applicable_default',
                'value' => 'true',
                'description' => 'Default NSSF-applicable flag for new guards/employees. Not yet read by any code path — GuardController currently expects this to be set explicitly per guard.',
            ],
            [
                'key' => 'paye_applicable_default',
                'value' => 'true',
                'description' => 'Default PAYE-applicable flag for new guards/employees. Not yet read by any code path — GuardController currently expects this to be set explicitly per guard.',
            ],
            [
                'key' => 'paye_currency',
                'value' => 'TZS',
                'description' => 'Currency PAYE/payroll figures are denominated in. Not yet read by any code path — display currently hardcodes "TZS" in the frontend.',
            ],

            // Employer-only — company cost, must never reduce employee net pay
            [
                'key' => 'sdl_rate',
                'value' => '3.5',
                'description' => 'Skills Development Levy, employer-only cost (%). Applies only when company headcount meets sdl_min_employees. Not yet read by any code path.',
            ],
            [
                'key' => 'sdl_min_employees',
                'value' => '10',
                'description' => 'Minimum company headcount for SDL to apply. Not yet read by any code path.',
            ],
            [
                'key' => 'wcf_rate',
                'value' => '0.5',
                'description' => 'Workers Compensation Fund, private-sector employer-only cost (%). Not yet read by any code path.',
            ],
        ];

        foreach ($settings as $setting) {
            PayrollSetting::updateOrCreate(['key' => $setting['key']], [
                'value' => $setting['value'],
                'description' => $setting['description'],
            ]);
        }

        // PAYE bands — monthly taxable income after employee NSSF.
        // rate_percentage stored as a whole percentage (8, not 0.08),
        // matching calculatePaye()'s existing (/100) convention.
        $brackets = [
            ['min_amount' => 0,       'max_amount' => 270000,   'base_tax' => 0,      'rate_percentage' => 0],
            ['min_amount' => 270001,  'max_amount' => 520000,   'base_tax' => 0,      'rate_percentage' => 8],
            ['min_amount' => 520001,  'max_amount' => 760000,   'base_tax' => 20000,  'rate_percentage' => 20],
            ['min_amount' => 760001,  'max_amount' => 1000000,  'base_tax' => 68000,  'rate_percentage' => 25],
            ['min_amount' => 1000001, 'max_amount' => null,     'base_tax' => 128000, 'rate_percentage' => 30],
        ];

        foreach ($brackets as $bracket) {
            PayrollTaxBracket::updateOrCreate(
                ['min_amount' => $bracket['min_amount']],
                $bracket
            );
        }
    }
}