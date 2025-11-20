<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\VehiculoAsignacion;


// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::apiResource('vehiculos', VehiculoController::class);

    // Listar todas las asignaciones
    Route::get('/asignaciones', [VehiculoAsignacion::class, 'index']);
    // Asignar vehículo a conductor
    Route::post('/vehiculos/{vehicleId}/asignar', [VehiculoAsignacion::class, 'assignVehicle']);
    // Desasignar vehículo
    Route::post('/vehiculos/{vehicleId}/desasignar', [VehiculoAsignacion::class, 'unassignVehicle']);
    // Historial de asignaciones de un vehículo
    Route::get('/vehiculos/{vehicleId}/asignaciones', [VehiculoAsignacion::class, 'getVehicleAssignmentHistory']);
    // Obtener vehículos con información de conductores
    Route::get('/vehiculos-con-conductores', [VehiculoAsignacion::class, 'getVehiclesWithDrivers']);
    // Obtener todos los conductores (con o sin vehículo)
    Route::get('/conductores', [VehiculoAsignacion::class, 'getAllDrivers']);
    // Obtener conductores disponibles (sin vehículo)
    Route::get('/conductores/disponibles', [VehiculoAsignacion::class, 'getAvailableDrivers']);
    // Historial de asignaciones de un conductor
    Route::get('/conductores/{driverId}/asignaciones', [VehiculoAsignacion::class, 'getDriverAssignmentHistory']);
    // Asignación activa de un conductor
    Route::get('/conductores/{driverId}/asignacion-activa', [VehiculoAsignacion::class, 'getActiveAssignment']);

    // Obtener usuarios con filtros opcionales
    Route::get('/usuarios', function (Request $request) {
        $role = $request->query('rol');
        $disponible = $request->query('disponible');

        $query = \App\Models\User::query();

        if ($role) {
            $query->where('role', $role);
        }

        if ($disponible === 'true') {
            $query->whereNull('current_vehicle_id');
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(['id', 'name', 'email', 'role', 'current_vehicle_id'])
        ]);
    });
});
