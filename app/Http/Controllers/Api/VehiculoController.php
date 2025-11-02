<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Validator;

class VehiculoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $vehiculo = Vehiculo::where('user_id', $request->user()->id)->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $vehiculo], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehiculo_id' => 'nullable|string',
            'placa' => 'required|string|max:20',
            'marca' => 'nullable|string|max:50',
            'modelo' => 'nullable|string|max:50',
            //'activo' => 'boolean',
            'perfil_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        //verificar si exite un vehiculo con el vehiculo_id del serverProfe
        try {
            $existeCar = Vehiculo::where('vehiculo_id', $request->vehiculo_id)->where('user_id', $request->user()->id)->first();

            if ($existeCar) {
                //lo actualizamos
                 $vehiculoExistente->update($request->only([
                    'placa', 'marca', 'modelo', 'perfil_id'
                ]));


                return response()->json([
                    'message' => 'Vehículo actualizado exitosamente',
                    'data' => $existeCar
                ], 200);
            }

            // Crear nuevo vehículo
            $vehiculo = Vehiculo::create([
                'vehiculo_id' => $request->vehiculo_id,
                'placa' => $request->placa,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                //'activo' => $request->activo ?? true,
                'user_id' => $request->user()->id,
                'perfil_id' => $request->perfil_id,
            ]);

            return response()->json([
                'message' => 'Vehículo creado exitosamente',
                'data' => $vehiculo
            ], 201);
        }catch (\Exception $e){
            return response()->json([
                'message' => 'Error al crear o actualizar el vehiculo',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $car = Vehiculo::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$car) {
            return response()->json([
                'message' => 'Vehículo no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $car
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $car = Vehiculo::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$car) {
            return response()->json([
                'message' => 'Vehículo no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'placa' => 'sometimes|required|string|max:20',
            'marca' => 'nullable|string|max:50',
            'modelo' => 'nullable|string|max:50'
            //'activo' => 'nullable|boolean'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Error de validacion',
                'errors' => $validator->errors()
            ], 422);
        }

        $car->update($request->only([
            'placa',
            'marca',
            'modelo'
            //'activo'
        ]));

        return response()->json([
            'message' => 'Vehículo actualizado',
            'data' => $vehiculo
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $car = Vehiculo::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$car) {
            return response()->json([
                'message' => 'Vehículo no encontrado'
            ], 404);
        }

        $car->delete();

        return response()->json([
            'message' => 'Vehículo eliminado'
        ], 200);
    }



}
