<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Http\Requests\StoreVehicleRequest;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $schoolId = app('current_school_id');
        $vehicles = Vehicle::where('school_id', $schoolId)->paginate(15);

        return view('admin.transport.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $drivers = \App\Models\User::where('school_id', app('current_school_id'))
            ->where('role', 'chauffeur')
            ->get();

        return view('admin.transport.vehicles.create', compact('drivers'));
    }

    public function store(StoreVehicleRequest $request)
    {
        Vehicle::create([
            'school_id' => app('current_school_id'),
            ...$request->validated(),
        ]);

        return redirect()->route('admin.transport.vehicles.index')
            ->with('success', 'Vehicule cree avec succes.');
    }

    public function show(Vehicle $vehicle)
    {
        abort_unless($vehicle->school_id === app('current_school_id'), 403);

        return view('admin.transport.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        abort_unless($vehicle->school_id === app('current_school_id'), 403);

        $drivers = \App\Models\User::where('school_id', app('current_school_id'))
            ->where('role', 'chauffeur')
            ->get();

        return view('admin.transport.vehicles.edit', compact('vehicle', 'drivers'));
    }

    public function update(StoreVehicleRequest $request, Vehicle $vehicle)
    {
        abort_unless($vehicle->school_id === app('current_school_id'), 403);

        $vehicle->update($request->validated());

        return redirect()->route('admin.transport.vehicles.show', $vehicle)
            ->with('success', 'Vehicule mis a jour avec succes.');
    }

    public function destroy(Vehicle $vehicle)
    {
        abort_unless($vehicle->school_id === app('current_school_id'), 403);

        $vehicle->delete();

        return redirect()->route('admin.transport.vehicles.index')
            ->with('success', 'Vehicule supprime avec succes.');
    }
}
