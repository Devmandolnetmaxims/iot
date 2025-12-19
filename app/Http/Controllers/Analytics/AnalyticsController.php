<?php

namespace App\Http\Controllers\Analytics;

use App\Services\Analytics\AnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    // All the function is for testing prupose

    // Load 6 hourse raw data in temp table
    public function load6hrawdata(Request $request) {
        return AnalyticsService::Load6hrawdata($request);
    }
}
