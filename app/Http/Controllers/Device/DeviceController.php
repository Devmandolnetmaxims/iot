<?php

namespace App\Http\Controllers\Device;

use App\Http\Repository\Device\DeviceRepository;
use App\Http\Requests\Devices\CreateRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        return DeviceRepository::Index($request);
    }

    public function show($id)
    {
        return DeviceRepository::getDeviceById($id);
    }

    public function store(CreateRequest $request)
    {
        return DeviceRepository::create($request);
    }

    // Update
    public function update(Request $request, $id) {
        return DeviceRepository::Update($request, $id);
    }

    // Delete
    public function destroy($id) {
        return DeviceRepository::Delete($id);
    }
}
