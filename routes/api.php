<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\VehiculoAsignacion;
use App\Http\Controllers\Api\RecorridoController;
use App\Http\Controllers\Api\PosicionController;

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
    // Obtener todos los conductores
    Route::get('/conductores', [VehiculoAsignacion::class, 'getAllDrivers']);
    // Obtener conductores disponibles
    Route::get('/conductores/disponibles', [VehiculoAsignacion::class, 'getAvailableDrivers']);
    // Historial de asignaciones de un conductor
    Route::get('/conductores/{driverId}/asignaciones', [VehiculoAsignacion::class, 'getDriverAssignmentHistory']);
    // Asignación activa de un conductor
    Route::get('/conductores/{driverId}/asignacion-activa', [VehiculoAsignacion::class, 'getActiveAssignment']);
    // Crear nuevo recorrido
    Route::post('/recorridos', [RecorridoController::class, 'store']);
    // Obtener un recorrido específico
    Route::get('/recorridos/{recorridoRemotoId}', [RecorridoController::class, 'show']);
    // Finalizar un recorrido
    Route::put('/recorridos/{recorridoRemotoId}/finalizar', [RecorridoController::class, 'finalizar']);
    // Obtener recorridos por conductor
    Route::get('/conductores/{conductorId}/recorridos', [RecorridoController::class, 'porConductor']);
    // Obtener recorridos activos por conductor
    Route::get('/conductores/{conductorId}/recorridos/activos', [RecorridoController::class, 'activosPorConductor']);
    // Obtener estadísticas de un conductor
    Route::get('/conductores/{conductorId}/estadisticas', [RecorridoController::class, 'estadisticasConductor']);
    // Obtener recorridos por rango de fechas
    Route::get('/recorridos-por-fechas', [RecorridoController::class, 'porFechas']);
    // Obtener recorridos por vehículo
    Route::get('/vehiculos/{vehiculoId}/recorridos', [RecorridoController::class, 'porVehiculo']);
    // Registrar nueva posición
    Route::post('/posiciones', [PosicionController::class, 'store']);
    // Registrar múltiples posiciones a la vez
    Route::post('/posiciones/batch', [PosicionController::class, 'storeBatch']);
    // Obtener todas las posiciones de un recorrido
    Route::get('/recorridos/{recorridoId}/posiciones', [PosicionController::class, 'porRecorrido']);
    // Obtener últimas N posiciones de un recorrido
    Route::get('/recorridos/{recorridoId}/posiciones/ultimas/{cantidad?}', [PosicionController::class, 'ultimasPosiciones']);
    // Obtener posiciones en formato GeoJSON
    Route::get('/recorridos/{recorridoId}/posiciones/geojson', [PosicionController::class, 'geoJson']);
    // Obtener el rastro de un recorrido
    Route::get('/recorridos/{recorridoId}/rastro', [PosicionController::class, 'rastro']);
    // Obtener estadísticas de posiciones de un recorrido
    Route::get('/recorridos/{recorridoId}/posiciones/estadisticas', [PosicionController::class, 'estadisticas']);

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
