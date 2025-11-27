<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|string|in:admin,conductor'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'conductor',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Usuario registrado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ],
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en registro: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            $user->load('currentVehicle:id,placa,marca,modelo,activo');

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Login exitoso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'current_vehicle_id' => $user->current_vehicle_id,
                'tiene_vehiculo' => !!$user->currentVehicle
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'vehiculo_asignado' => $user->currentVehicle ? [
                            'id' => $user->currentVehicle->id,
                            'placa' => $user->currentVehicle->placa,
                            'marca' => $user->currentVehicle->marca,
                            'modelo' => $user->currentVehicle->modelo,
                            'activo' => $user->currentVehicle->activo
                        ] : null
                    ],
                    'token' => $token
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $user->load('currentVehicle:id,placa,marca,modelo,activo');
            Log::info('Endpoint /api/user llamado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'current_vehicle_id' => $user->current_vehicle_id,
                'vehiculo_cargado' => !!$user->currentVehicle,
                'vehiculo_data' => $user->currentVehicle ? [
                    'id' => $user->currentVehicle->id,
                    'placa' => $user->currentVehicle->placa
                ] : null
            ]);

            return response()->json([
                'id' => $user->id,
                'nombre' => $user->name,
                'email' => $user->email,
                'rol' => $user->role,
                'vehiculo_asignado' => $user->currentVehicle ? [
                    'id' => $user->currentVehicle->id,
                    'placa' => $user->currentVehicle->placa,
                    'marca' => $user->currentVehicle->marca,
                    'modelo' => $user->currentVehicle->modelo,
                    'activo' => $user->currentVehicle->activo
                ] : null
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en /api/user: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener datos del usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                $user->currentAccessToken()->delete();

                Log::info('Logout exitoso', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en logout: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada'
            ], 200);
        }
    }
}
