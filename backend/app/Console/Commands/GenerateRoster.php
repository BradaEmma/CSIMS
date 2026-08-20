<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RosterEngineService;

class GenerateRoster extends Command
{
    protected $signature = 'roster:generate {start_date?}';
    protected $description = 'Generate weekly roster for guards automatically';

    public function handle()
    {
        $startDate = $this->argument('start_date') 
            ?? now()->startOfWeek()->toDateString();

        $this->info("Generating roster starting from: {$startDate}");

        $result = app(RosterEngineService::class)
            ->generateWeeklyRoster($startDate);

        if ($result['success']) {
            $this->info("Roster generated successfully!");
            $this->info("Assignments created: " . $result['created_assignments']);

            if (!empty($result['shortages'])) {
                $this->warn("⚠ Shortages detected:");
                foreach ($result['shortages'] as $shortage) {
                    $this->warn(
                        "{$shortage['date']} - {$shortage['site']} - {$shortage['shift']} (Missing: {$shortage['missing']})"
                    );
                }
            }
        } else {
            $this->error("Roster generation failed.");
        }

        return 0;
    }
}