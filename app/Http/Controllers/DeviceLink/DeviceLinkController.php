<?php

namespace App\Http\Controllers\DeviceLink;

use App\Http\Requests\DeviceLink\DeviceLinkCreateRequest;
use App\Http\Repository\DeviceLink\DeviceLinkRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceLinkController extends Controller
{
    // Read function
    public function index(Request $request) {
        return DeviceLinkRepository::Index($request);
    }

    // Create function
    public function store(DeviceLinkCreateRequest $request) {
        return DeviceLinkRepository::CreateDeviceLink($request->all());
    }
}
