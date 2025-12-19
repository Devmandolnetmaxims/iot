<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Analytics\AnalyticsService;

class Load6hRawData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load6h-raw-data {--time=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load last 6 hours raw device logs into staging table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $request = new \stdClass();
        $request->time = $this->option('time'); // null if not passed

        if(AnalyticsService::Load6hrawdata($request)) {
            $this->info('Raw data loaded.');
        } else {
            $this->error('Raw data not loaded.');
        }
    }
}
