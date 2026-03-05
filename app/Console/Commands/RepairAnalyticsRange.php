<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Analytics\AnalyticsService;

class RepairAnalyticsRange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:repair-analytics-range';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle() {
        // The days you want to process
        $days = ['07', '08', '09', '10', '11', '12'];
        // The specific time slots
        $hours = ['01:00:00', '07:00:00', '13:00:00', '18:00:00'];

        foreach ($days as $day) {
            foreach ($hours as $hour) {
                $timestamp = "2026-02-$day $hour";

                $this->info("-----------------------------------------");
                $this->info("Processing Slot: $timestamp");

                // Replicating your handle logic:
                $request = new \stdClass();
                $request->time = $timestamp;

                // 1. Load the data (Just like your handle function)
                if (AnalyticsService::Load6hrawdata($request)) {
                    $this->info("Raw data loaded for $timestamp.");
                } else {
                    $this->error("Raw data NOT loaded for $timestamp.");
                }
            }
        }

        $this->info("Historical batch from Feb 07 to Feb 13 completed.");
    }
}
