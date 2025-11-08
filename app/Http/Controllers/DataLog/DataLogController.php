<?php

namespace App\Http\Controllers\DataLog;

use App\Http\Repository\DataLog\DataLogRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DataLogController extends Controller
{
    // get data
    public function index(Request $request) {
        return DataLogRepository::Index($request);
    }
}
