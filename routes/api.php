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

// Endpoint público para obtener UUID del vehículo (requerido por frontend)
// DEBE ESTAR ANTES del apiResource para que no sea interceptado
Route::get('/vehiculos/uuid/{id}', [VehiculoController::class, 'getUuid']);

// rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // vehículos
    //Route::apiResource('vehiculos', VehiculoController::class);

    // asignaciones de vehículos
    Route::get('/asignaciones', [VehiculoAsignacion::class, 'index']);
    Route::post('/vehiculos/{vehicleId}/asignar', [VehiculoAsignacion::class, 'assignVehicle']);
    Route::post('/vehiculos/{vehicleId}/desasignar', [VehiculoAsignacion::class, 'unassignVehicle']);
    Route::get('/vehiculos/{vehicleId}/asignaciones', [VehiculoAsignacion::class, 'getVehicleAssignmentHistory']);
    Route::get('/vehiculos-con-conductores', [VehiculoAsignacion::class, 'getVehiclesWithDrivers']);

    // conductores
    Route::get('/conductores', [VehiculoAsignacion::class, 'getAllDrivers']);
    Route::get('/conductores/disponibles', [VehiculoAsignacion::class, 'getAvailableDrivers']);
    Route::get('/conductores/{driverId}/asignaciones', [VehiculoAsignacion::class, 'getDriverAssignmentHistory']);
    Route::get('/conductores/{driverId}/asignacion-activa', [VehiculoAsignacion::class, 'getActiveAssignment']);

    // recorridos
    Route::get('/recorridos-por-fechas', [RecorridoController::class, 'porFechas']);
    Route::get('/conductores/{conductorId}/recorridos', [RecorridoController::class, 'porConductor']);
    Route::get('/conductores/{conductorId}/recorridos/activos', [RecorridoController::class, 'activosPorConductor']);
    Route::get('/conductores/{conductorId}/estadisticas', [RecorridoController::class, 'estadisticasConductor']);
    Route::get('/vehiculos/{vehiculoId}/recorridos', [RecorridoController::class, 'porVehiculo']);

    // recorridos
    Route::post('/recorridos', [RecorridoController::class, 'store']);
    Route::get('/recorridos/{recorridoRemotoId}', [RecorridoController::class, 'show']);
    Route::put('/recorridos/{recorridoRemotoId}/finalizar', [RecorridoController::class, 'finalizar']);

    // posiciones
    Route::post('/posiciones/batch', [PosicionController::class, 'storeBatch']);
    Route::get('/recorridos/{recorridoId}/posiciones/ultimas/{cantidad?}', [PosicionController::class, 'ultimasPosiciones']);
    Route::get('/recorridos/{recorridoId}/posiciones/geojson', [PosicionController::class, 'geoJson']);
    Route::get('/recorridos/{recorridoId}/posiciones/estadisticas', [PosicionController::class, 'estadisticas']);
    Route::get('/recorridos/{recorridoId}/rastro', [PosicionController::class, 'rastro']);

    // posiciones CRUD
    Route::post('/posiciones', [PosicionController::class, 'store']);
    Route::get('/recorridos/{recorridoId}/posiciones', [PosicionController::class, 'porRecorrido']);

    // para obtener usuarios con filtros opcionales
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

//temporal route to fix unhashed passwords
