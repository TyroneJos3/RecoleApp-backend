<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recorrido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RecorridoController extends Controller
{

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recorrido_remoto_id' => 'required|string|unique:recorridos,recorrido_remoto_id',
            'ruta_id' => 'required|string',
            'vehiculo_id' => 'required|string',
            'conductor_id' => 'required|string',
            'inicio' => 'required|date',
            'activo' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recorrido = Recorrido::create([
                'recorrido_remoto_id' => $request->recorrido_remoto_id,
                'ruta_id' => $request->ruta_id,
                'vehiculo_id' => $request->vehiculo_id,
                'conductor_id' => $request->conductor_id,
                'inicio' => $request->inicio,
                'activo' => $request->activo ?? true,
            ]);

            Log::info('Recorrido creado exitosamente', [
                'recorrido_id' => $recorrido->id,
                'recorrido_remoto_id' => $recorrido->recorrido_remoto_id,
                'conductor_id' => $recorrido->conductor_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recorrido iniciado correctamente',
                'data' => $recorrido
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear recorrido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el recorrido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($recorridoRemotoId)
    {
        try {
            $recorrido = Recorrido::where('recorrido_remoto_id', $recorridoRemotoId)
                ->with('posiciones')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $recorrido
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recorrido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el recorrido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function finalizar(Request $request, $recorridoRemotoId)
    {
        $validator = Validator::make($request->all(), [
            'fin' => 'required|date',
            'distancia_total' => 'required|numeric|min:0',
            'duracion' => 'required|integer|min:0',
            'puntos_totales' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recorrido = Recorrido::where('recorrido_remoto_id', $recorridoRemotoId)
                ->firstOrFail();

            if (!$recorrido->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El recorrido ya está finalizado'
                ], 400);
            }

            $recorrido->update([
                'fin' => $request->fin,
                'distancia_total' => $request->distancia_total,
                'duracion' => $request->duracion,
                'puntos_totales' => $request->puntos_totales,
                'activo' => false,
            ]);

            Log::info('Recorrido finalizado exitosamente', [
                'recorrido_id' => $recorrido->id,
                'recorrido_remoto_id' => $recorridoRemotoId,
                'distancia' => $request->distancia_total,
                'duracion' => $request->duracion
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recorrido finalizado correctamente',
                'data' => $recorrido
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recorrido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al finalizar recorrido', [
                'recorrido_remoto_id' => $recorridoRemotoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar el recorrido',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function porConductor($conductorId)
    {
        try {
            $recorridos = Recorrido::porConductor($conductorId)
                ->orderBy('inicio', 'desc')
                ->with('posiciones')
                ->get();

            return response()->json([
                'success' => true,
                'total' => $recorridos->count(),
                'data' => $recorridos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener recorridos por conductor', [
                'conductor_id' => $conductorId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recorridos',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function activosPorConductor($conductorId)
    {
        try {
            $recorridos = Recorrido::porConductor($conductorId)
                ->activos()
                ->with('posiciones')
                ->get();

            return response()->json([
                'success' => true,
                'total' => $recorridos->count(),
                'data' => $recorridos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recorridos activos',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function estadisticasConductor($conductorId)
    {
        try {
            $recorridos = Recorrido::porConductor($conductorId)->get();
            $recorridosFinalizados = $recorridos->where('activo', false);

            $estadisticas = [
                'total_recorridos' => $recorridos->count(),
                'recorridos_activos' => $recorridos->where('activo', true)->count(),
                'recorridos_completados' => $recorridosFinalizados->count(),
                'distancia_total_km' => round($recorridos->sum('distancia_total'), 2),
                'tiempo_total_segundos' => $recorridos->sum('duracion'),
                'tiempo_total_formateado' => $this->formatearSegundos($recorridos->sum('duracion')),
                'puntos_totales' => $recorridos->sum('puntos_totales'),
                'velocidad_promedio_kmh' => $recorridosFinalizados->count() > 0
                    ? round($recorridosFinalizados->avg('velocidad_promedio'), 2)
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function porFechas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'conductor_id' => 'sometimes|string',
            'vehiculo_id' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Recorrido::entreFechas(
                $request->fecha_inicio,
                $request->fecha_fin
            );

            if ($request->has('conductor_id')) {
                $query->porConductor($request->conductor_id);
            }

            if ($request->has('vehiculo_id')) {
                $query->porVehiculo($request->vehiculo_id);
            }

            $recorridos = $query->orderBy('inicio', 'desc')->get();

            return response()->json([
                'success' => true,
                'total' => $recorridos->count(),
                'filtros' => [
                    'fecha_inicio' => $request->fecha_inicio,
                    'fecha_fin' => $request->fecha_fin,
                    'conductor_id' => $request->conductor_id,
                    'vehiculo_id' => $request->vehiculo_id,
                ],
                'data' => $recorridos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recorridos por fechas',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function porVehiculo($vehiculoId)
    {
        try {
            $recorridos = Recorrido::porVehiculo($vehiculoId)
                ->orderBy('inicio', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'total' => $recorridos->count(),
                'data' => $recorridos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recorridos por vehículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function formatearSegundos($segundos)
    {
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segs);
    }
}
