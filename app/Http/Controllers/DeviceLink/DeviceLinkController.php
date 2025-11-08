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

    // Update function
    public function update(Request $request, $id) {
        return DeviceLinkRepository::UpdateDeviceLink($id, $request->all());
    }

    // Delete function
    public function destroy($id) {
        return DeviceLinkRepository::DeleteDeviceLink($id);
    }

    // Get all car numbers
    public function getAllCars() {
        return DeviceLinkRepository::GetAllCars();
    }
}
