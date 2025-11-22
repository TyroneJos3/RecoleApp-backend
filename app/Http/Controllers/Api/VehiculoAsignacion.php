<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\User;
use App\Models\Asignacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class VehiculoAsignacion extends Controller
{

    public function assignVehicle(Request $request, $vehicleId)
    {
        Log::info('Asignando vehículo', [
            'vehicle_id' => $vehicleId,
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|exists:users,id',
            'fecha_asignacion' => 'sometimes|date',
            'notes' => 'nullable|string',
            'force' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida', [
                'errors' => $validator->errors()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        return DB::transaction(function () use ($request, $vehicleId) {
            try {
                // Buscar vehículo
                $vehicle = Vehiculo::findOrFail($vehicleId);
                Log::info('Vehículo encontrado', ['vehicle' => $vehicle]);

                // Buscar conductor
                $driver = User::where('id', $request->usuario_id)
                            ->where('role', 'conductor')
                            ->first();

                if (!$driver) {
                    Log::error('Usuario no es conductor', [
                        'user_id' => $request->usuario_id
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'El usuario no existe o no es conductor'
                    ], 404);
                }

                Log::info('Conductor encontrado', ['driver' => $driver]);

                // Si el conductor ya tiene un vehículo asignado, desasignarlo primero
                if ($driver->current_vehicle_id && $driver->current_vehicle_id != $vehicleId) {
                    Log::info('Conductor tiene vehículo previo, desasignando', [
                        'previous_vehicle_id' => $driver->current_vehicle_id
                    ]);

                    $previousVehicle = Vehiculo::find($driver->current_vehicle_id);
                    if ($previousVehicle) {
                        $previousVehicle->update(['current_driver_id' => null]);

                        // Finalizar asignación anterior
                        Asignacion::where('vehicle_id', $driver->current_vehicle_id)
                            ->where('driver_id', $driver->id)
                            ->where('status', 'active')
                            ->update([
                                'unassignment_date' => now(),
                                'status' => 'completed',
                                'notes' => 'Reasignado automáticamente a otro vehículo'
                            ]);
                    }
                }

                // Si el vehículo ya está asignado a otro conductor, desasignarlo
                if ($vehicle->current_driver_id && $vehicle->current_driver_id != $request->usuario_id) {
                    Log::info('Vehículo tiene conductor previo, desasignando', [
                        'previous_driver_id' => $vehicle->current_driver_id
                    ]);

                    $previousDriver = User::find($vehicle->current_driver_id);
                    if ($previousDriver) {
                        $previousDriver->update(['current_vehicle_id' => null]);

                        // Finalizar asignación anterior
                        Asignacion::where('vehicle_id', $vehicleId)
                            ->where('driver_id', $previousDriver->id)
                            ->where('status', 'active')
                            ->update([
                                'unassignment_date' => now(),
                                'status' => 'completed',
                                'notes' => 'Reasignado automáticamente a otro conductor'
                            ]);
                    }
                }

                // Crear nueva asignación
                $assignment = Asignacion::create([
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $request->usuario_id,
                    'assignment_date' => $request->fecha_asignacion ?? now(),
                    'status' => 'active',
                    'notes' => $request->notes
                ]);

                Log::info('Asignación creada', ['assignment' => $assignment]);

                // Actualizar vehículo
                $vehicle->update([
                    'current_driver_id' => $request->usuario_id
                ]);

                // Actualizar conductor
                $driver->update([
                    'current_vehicle_id' => $vehicleId,
                    'is_active_driver' => true
                ]);

                Log::info('Asignación completada exitosamente');

                return response()->json([
                    'success' => true,
                    'message' => 'Vehículo asignado exitosamente',
                    'data' => [
                        'assignment' => $assignment->load(['vehicle', 'driver']),
                        'vehicle' => $vehicle->fresh()->load('currentDriver'),
                        'driver' => $driver->fresh()->load('currentVehicle')
                    ]
                ], 201);

            } catch (\Exception $e) {
                Log::error('Error en assignVehicle', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error al asignar vehículo: ' . $e->getMessage()
                ], 500);
            }
        });
    }


    public function unassignVehicle(Request $request, $vehicleId)
    {
        Log::info('Desasignando vehículo', [
            'vehicle_id' => $vehicleId
        ]);

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        return DB::transaction(function () use ($request, $vehicleId) {
            try {
                $vehicle = Vehiculo::findOrFail($vehicleId);

                if (!$vehicle->current_driver_id) {
                    return response()->json([
                        'success' => false,
                        'error' => 'El vehículo no está asignado a ningún conductor'
                    ], 400);
                }

                $assignment = Asignacion::where('vehicle_id', $vehicleId)
                                ->where('status', 'active')
                                ->first();

                if (!$assignment) {
                    Log::warning('No se encontró asignación activa, pero el vehículo tiene conductor asignado');
                    $vehicle->update(['current_driver_id' => null]);

                    if ($driver = User::find($vehicle->current_driver_id)) {
                        $driver->update(['current_vehicle_id' => null]);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Vehículo desasignado (limpieza de datos inconsistentes)'
                    ]);
                }

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

                Log::info('Desasignación completada exitosamente');

                return response()->json([
                    'success' => true,
                    'message' => 'Vehículo desasignado exitosamente',
                    'data' => [
                        'assignment' => $assignment,
                        'vehicle' => $vehicle,
                        'driver' => $driver
                    ]
                ]);

            } catch (\Exception $e) {
                Log::error('Error en unassignVehicle', [
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error al desasignar vehículo: ' . $e->getMessage()
                ], 500);
            }
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
            $query->with('currentDriver:id,name,email,role,current_vehicle_id');
        }

        $vehicles = $query->get();

        $vehicles->transform(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'placa' => $vehicle->placa,
                'marca' => $vehicle->marca,
                'modelo' => $vehicle->modelo,
                'activo' => (bool)$vehicle->activo,
                'current_driver_id' => $vehicle->current_driver_id,
                'conductor_actual' => $vehicle->currentDriver ? [
                    'id' => $vehicle->currentDriver->id,
                    'nombre' => $vehicle->currentDriver->name,
                    'email' => $vehicle->currentDriver->email,
                    'role' => $vehicle->currentDriver->role
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }


   public function getAvailableDrivers()
    {
        $drivers = User::where('role', 'conductor')
                    ->whereNull('current_vehicle_id')
                    ->select('id', 'name', 'email', 'driver_license', 'role', 'current_vehicle_id')
                    ->get();

        // Transformar la respuesta
        $drivers->transform(function ($driver) {
            return [
                'id' => $driver->id,
                'nombre' => $driver->name,
                'email' => $driver->email,
                'disponible' => true,
                'vehiculo_asignado' => null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }


    public function getAllDrivers()
    {
        $drivers = User::where('role', 'conductor')
                    ->with('currentVehicle:id,placa,marca,modelo')
                    ->select('id', 'name', 'email', 'driver_license', 'role', 'current_vehicle_id')
                    ->get();

        // Transformar la respuesta
        $drivers->transform(function ($driver) {
            return [
                'id' => $driver->id,
                'nombre' => $driver->name,
                'email' => $driver->email,
                'disponible' => !$driver->current_vehicle_id,
                'vehiculo_asignado' => $driver->currentVehicle ? [
                    'id' => $driver->currentVehicle->id,
                    'placa' => $driver->currentVehicle->placa,
                    'marca' => $driver->currentVehicle->marca,
                    'modelo' => $driver->currentVehicle->modelo
                ] : null
            ];
        });

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

    public function getActiveAssignment($driverId)
    {
        $assignment = Asignacion::where('status', 'active')
                        ->where('driver_id', $driverId)
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
