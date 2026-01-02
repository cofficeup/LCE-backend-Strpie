<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Pickup\RecurringPickupService;
use Carbon\Carbon;

class GenerateRecurringPickups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickups:generate-recurring {date? : The date to generate for (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring pickups for the given date (default: tomorrow)';

    /**
     * Execute the console command.
     */
    public function handle(RecurringPickupService $service)
    {
        $dateInput = $this->argument('date');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::tomorrow();

        $this->info("Generating recurring pickups for: " . $date->format('Y-m-d'));

        $count = $service->generatePickupsForDate($date);

        $this->info("Successfully generated {$count} pickups.");
    }
}
