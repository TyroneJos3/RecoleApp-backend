<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\User;
use App\Models\Asignacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VehiculoAsignacion extends Controller
{

    public function assignVehicle(Request $request, $vehicleId)
    {
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|exists:users,id',
            'fecha_asignacion' => 'sometimes|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        return DB::transaction(function () use ($request, $vehicleId) {
            $vehicle = Vehiculo::findOrFail($vehicleId);

            $driver = User::where('id', $request->usuario_id)
                        ->where('role', 'conductor')
                        ->firstOrFail();

            if ($driver->current_vehicle_id) {
                return response()->json([
                    'error' => 'El conductor ya tiene un vehículo asignado',
                    'current_vehicle_id' => $driver->current_vehicle_id
                ], 400);
            }

            if ($vehicle->current_driver_id) {
                return response()->json([
                    'error' => 'El vehículo ya está asignado a otro conductor',
                    'current_driver_id' => $vehicle->current_driver_id
                ], 400);
            }

            $assignment = Asignacion::create([
                'vehicle_id' => $vehicleId,
                'driver_id' => $request->usuario_id,
                'assignment_date' => $request->fecha_asignacion ?? now(),
                'status' => 'active',
                'notes' => $request->notes
            ]);

            $vehicle->update([
                'current_driver_id' => $request->usuario_id
            ]);

            $driver->update([
                'current_vehicle_id' => $vehicleId,
                'is_active_driver' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehículo asignado exitosamente',
                'data' => [
                    'assignment' => $assignment->load(['vehicle', 'driver']),
                    'vehicle' => $vehicle->fresh('currentDriver'),
                    'driver' => $driver->fresh('currentVehicle')
                ]
            ], 201);
        });
    }


    public function unassignVehicle(Request $request, $vehicleId)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        return DB::transaction(function () use ($request, $vehicleId) {
            $vehicle = Vehiculo::findOrFail($vehicleId);

            if (!$vehicle->current_driver_id) {
                return response()->json([
                    'error' => 'El vehículo no está asignado a ningún conductor'
                ], 400);
            }

            $assignment = Asignacion::where('vehicle_id', $vehicleId)
                            ->where('status', 'active')
                            ->firstOrFail();

            $driver = User::findOrFail($vehicle->current_driver_id);

            $assignment->update([
                'unassignment_date' => now(),
                'status' => 'completed',
                'notes' => $request->notes ?? $assignment->notes
            ]);

            $vehicle->update([
                'current_driver_id' => null
            ]);

            $driver->update([
                'current_vehicle_id' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehículo desasignado exitosamente',
                'data' => [
                    'assignment' => $assignment,
                    'vehicle' => $vehicle,
                    'driver' => $driver
                ]
            ]);
        });
    }


    public function getVehiclesWithDrivers(Request $request)
    {
        $perfilId = $request->query('perfil_id');
        $includeDriver = $request->query('incluir_conductor', true);

        $query = Vehiculo::query();

        if ($perfilId) {
            $query->where('perfil_id', $perfilId);
        }

        if ($includeDriver) {
            $query->with('currentDriver');
        }

        $vehicles = $query->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }


   public function getAvailableDrivers()
{
    $drivers = User::where('role', 'conductor')
                ->whereNull('current_vehicle_id')
                ->select('id', 'name', 'email', 'driver_license', 'role')
                ->get();

    return response()->json([
        'success' => true,
        'data' => $drivers
    ]);
}


    public function getAllDrivers()
{
    $drivers = User::where('role', 'conductor')
                ->with('currentVehicle')
                ->select('id', 'name', 'email', 'driver_license', 'role', 'current_vehicle_id')
                ->get();

    return response()->json([
        'success' => true,
        'data' => $drivers
    ]);
}

    public function getVehicleAssignmentHistory($vehicleId)
    {
        $assignments = Asignacion::with('driver')
                        ->where('vehicle_id', $vehicleId)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Obtener historial de asignaciones de un conductor
     */
    public function getDriverAssignmentHistory($driverId)
    {
        $assignments = Asignacion::with('vehicle')
                        ->where('driver_id', $driverId)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Obtener asignación activa de un conductor
     */
    public function getActiveAssignment($driverId)
    {
        $assignment = Asignacion::active()
                        ->forDriver($driverId)
                        ->with(['vehicle', 'driver'])
                        ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'No hay asignación activa para este conductor'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $assignment
        ]);
    }


    public function index(Request $request)
    {
        $query = Asignacion::with(['vehicle', 'driver']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $assignments = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }
}
