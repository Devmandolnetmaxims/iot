<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsService;
use Illuminate\Console\Command;

class LoadHourlyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load-hourly-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $request = new \stdClass();
        // $request->time = $this->option('time'); // null if not passed

        if(AnalyticsService::Load1hrawdata()) {
            $this->info('Raw data loaded.');
        } else {
            $this->error('Raw data not loaded.');
        }
    }
}
