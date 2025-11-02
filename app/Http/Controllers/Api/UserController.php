<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
     public function index() {
        $usuarios = User::all();

        if($usuarios->isEmpty()){
            return response()->json([
                'success' => false,
                'message' => 'No hay usuarios registrados'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuarios obtenidos exitosamente',
            'users' => $usuarios,
        ], 200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación de datos',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
        ], 201);
    }

    public function show($id) {
        $user = User::find($id);

        if(!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario obtenido exitosamente',
            'user' => $user
        ], 200);
    }

    public function destroy($id) {
        $user = User::find($id);

        if(!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ], 200);
    }

    public function update(Request $request, $id) {
        $user = User::find($id);

        if(!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$id,
            'password' => 'sometimes|required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación de datos',
                'errors' => $validator->errors()
            ], 400);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user
        ], 200);
    }
}
