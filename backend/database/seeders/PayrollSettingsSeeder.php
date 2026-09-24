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
     * UNIT CONVENTION: rates stored as whole percentages (10 = 10%), not 0.10.
     * PAYE is on (gross - employee NSSF). SDL and WCF are employer-only.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'nssf_employee_rate',
                'value' => '10',
                'description' => 'NSSF employee contribution rate (%), deducted from gross pay.',
            ],
            [
                'key' => 'nssf_employer_rate',
                'value' => '10',
                'description' => 'NSSF employer contribution rate (%) — company cost, never from net pay.',
            ],
            [
                'key' => 'nssf_applicable_default',
                'value' => 'true',
                'description' => 'Default NSSF-applicable flag for new guards/employees.',
            ],
            [
                'key' => 'paye_applicable_default',
                'value' => 'true',
                'description' => 'Default PAYE-applicable flag for new guards/employees.',
            ],
            [
                'key' => 'paye_currency',
                'value' => 'TZS',
                'description' => 'Currency for PAYE/payroll figures.',
            ],
            [
                'key' => 'sdl_rate',
                'value' => '3.5',
                'description' => 'Skills Development Levy, employer-only (%).',
            ],
            [
                'key' => 'sdl_min_employees',
                'value' => '10',
                'description' => 'Minimum company headcount for SDL to apply.',
            ],
            [
                'key' => 'wcf_rate',
                'value' => '0.5',
                'description' => 'Workers Compensation Fund, employer-only (%).',
            ],
        ];

        foreach ($settings as $setting) {
            PayrollSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                ]
            );
        }

        // CORRECTED 2026-09-23: min_amount was previously threshold+1
        // (270001, 520001, ...), which doesn't match Baraka's own worked
        // examples (gross 500,000 should yield PAYE exactly 14,400, not
        // 14,399.92). TRA convention taxes the amount ABOVE the round
        // threshold, not above threshold+1. Matched on max_amount here
        // (not min_amount), since min_amount is the field that changed —
        // matching on it would create 5 new orphaned rows instead of
        // correcting the existing 5.
        $brackets = [
            ['min_amount' => 0,      'max_amount' => 270000,  'base_tax' => 0,      'rate_percentage' => 0],
            ['min_amount' => 270000, 'max_amount' => 520000,  'base_tax' => 0,      'rate_percentage' => 8],
            ['min_amount' => 520000, 'max_amount' => 760000,  'base_tax' => 20000,  'rate_percentage' => 20],
            ['min_amount' => 760000, 'max_amount' => 1000000, 'base_tax' => 68000,  'rate_percentage' => 25],
            ['min_amount' => 1000000, 'max_amount' => null,   'base_tax' => 128000, 'rate_percentage' => 30],
        ];

        foreach ($brackets as $bracket) {
            PayrollTaxBracket::updateOrCreate(
                ['max_amount' => $bracket['max_amount']],
                $bracket
            );
        }
    }
}