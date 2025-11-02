<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehiculoController;

// Rutas API de usuarios
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Aquí van tus otras rutas protegidas (rutas, vehículos, etc.)
    Route::apiResource('vehiculos', VehiculoController::class);
    // Route::apiResource('vehiculos', VehiculoController::class);
    // Route::apiResource('rutas', RutaController::class);
});


