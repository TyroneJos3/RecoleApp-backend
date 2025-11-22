<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Posicion;
use App\Models\Recorrido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PosicionController extends Controller
{

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recorrido_id' => 'required|string',
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
            'timestamp' => 'sometimes|date',
            'altitud' => 'sometimes|numeric',
            'precision' => 'sometimes|numeric|min:0',
            'velocidad' => 'sometimes|numeric|min:0',
            'rumbo' => 'sometimes|numeric|between:0,360',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar que el recorrido existe y está activo
            $recorrido = Recorrido::where('recorrido_remoto_id', $request->recorrido_id)
                ->where('activo', true)
                ->first();

            if (!$recorrido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recorrido no encontrado o no está activo'
                ], 404);
            }

            $posicion = Posicion::create([
                'recorrido_id' => $request->recorrido_id,
                'lat' => $request->lat,
                'lon' => $request->lon,
                'timestamp' => $request->timestamp ?? now(),
                'altitud' => $request->altitud,
                'precision' => $request->precision,
                'velocidad' => $request->velocidad,
                'rumbo' => $request->rumbo,
            ]);

            // Log solo cada 10 posiciones para no saturar los logs
            if ($posicion->id % 10 === 0) {
                Log::info('Posición registrada', [
                    'recorrido_id' => $request->recorrido_id,
                    'posicion_id' => $posicion->id,
                    'lat' => $request->lat,
                    'lon' => $request->lon
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Posición registrada correctamente',
                'data' => $posicion
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al registrar posición', [
                'error' => $e->getMessage(),
                'recorrido_id' => $request->recorrido_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la posición',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function porRecorrido($recorridoId)
    {
        try {
            $posiciones = Posicion::porRecorrido($recorridoId)
                ->ordenadoPorTiempo()
                ->get();

            return response()->json([
                'success' => true,
                'total' => $posiciones->count(),
                'data' => $posiciones
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener posiciones', [
                'recorrido_id' => $recorridoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener posiciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ultimasPosiciones($recorridoId, $cantidad = 100)
    {
        try {
            $posiciones = Posicion::porRecorrido($recorridoId)
                ->orderBy('timestamp', 'desc')
                ->limit($cantidad)
                ->get()
                ->reverse()
                ->values();

            return response()->json([
                'success' => true,
                'total' => $posiciones->count(),
                'cantidad_solicitada' => $cantidad,
                'data' => $posiciones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener últimas posiciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function geoJson($recorridoId)
    {
        try {
            $posiciones = Posicion::porRecorrido($recorridoId)
                ->ordenadoPorTiempo()
                ->get();

            $features = $posiciones->map(function ($posicion) {
                return [
                    'type' => 'Feature',
                    'geometry' => $posicion->geo_json,
                    'properties' => [
                        'id' => $posicion->id,
                        'timestamp' => $posicion->timestamp->toISOString(),
                        'velocidad' => $posicion->velocidad,
                        'precision' => $posicion->precision,
                        'altitud' => $posicion->altitud,
                        'rumbo' => $posicion->rumbo,
                    ]
                ];
            });

            $geoJson = [
                'type' => 'FeatureCollection',
                'features' => $features
            ];

            return response()->json([
                'success' => true,
                'data' => $geoJson
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar GeoJSON',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function rastro($recorridoId)
    {
        try {
            $posiciones = Posicion::porRecorrido($recorridoId)
                ->ordenadoPorTiempo()
                ->get();

            if ($posiciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay posiciones registradas para este recorrido'
                ], 404);
            }

            $coordinates = $posiciones->map(function ($posicion) {
                return [(float) $posicion->lon, (float) $posicion->lat];
            })->toArray();

            $lineString = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => $coordinates
                ],
                'properties' => [
                    'recorrido_id' => $recorridoId,
                    'total_puntos' => count($coordinates),
                    'inicio' => $posiciones->first()->timestamp->toISOString(),
                    'fin' => $posiciones->last()->timestamp->toISOString(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $lineString
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar rastro',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recorrido_id' => 'required|string',
            'posiciones' => 'required|array|min:1|max:100',
            'posiciones.*.lat' => 'required|numeric|between:-90,90',
            'posiciones.*.lon' => 'required|numeric|between:-180,180',
            'posiciones.*.timestamp' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar que el recorrido existe
            $recorrido = Recorrido::where('recorrido_remoto_id', $request->recorrido_id)
                ->firstOrFail();

            $posicionesCreadas = [];
            foreach ($request->posiciones as $posData) {
                $posicion = Posicion::create([
                    'recorrido_id' => $request->recorrido_id,
                    'lat' => $posData['lat'],
                    'lon' => $posData['lon'],
                    'timestamp' => $posData['timestamp'] ?? now(),
                    'altitud' => $posData['altitud'] ?? null,
                    'precision' => $posData['precision'] ?? null,
                    'velocidad' => $posData['velocidad'] ?? null,
                    'rumbo' => $posData['rumbo'] ?? null,
                ]);
                $posicionesCreadas[] = $posicion;
            }

            Log::info('Posiciones batch registradas', [
                'recorrido_id' => $request->recorrido_id,
                'total' => count($posicionesCreadas)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Posiciones registradas correctamente',
                'total' => count($posicionesCreadas),
                'data' => $posicionesCreadas
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recorrido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al registrar posiciones batch', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar posiciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function estadisticas($recorridoId)
    {
        try {
            $posiciones = Posicion::porRecorrido($recorridoId)
                ->ordenadoPorTiempo()
                ->get();

            if ($posiciones->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay posiciones registradas para este recorrido',
                    'data' => null
                ]);
            }

            // Calcular distancia total recorrida
            $distanciaTotal = 0;
            for ($i = 1; $i < $posiciones->count(); $i++) {
                $distanciaTotal += $posiciones[$i - 1]->distanciaA($posiciones[$i]);
            }

            // Calcular duración
            $duracionSegundos = $posiciones->first()->timestamp->diffInSeconds($posiciones->last()->timestamp);

            $estadisticas = [
                'total_posiciones' => $posiciones->count(),
                'distancia_total_km' => round($distanciaTotal, 2),
                'duracion_segundos' => $duracionSegundos,
                'duracion_formateada' => $this->formatearSegundos($duracionSegundos),
                'primera_posicion' => [
                    'timestamp' => $posiciones->first()->timestamp->toISOString(),
                    'coordenadas' => $posiciones->first()->coordenadas,
                ],
                'ultima_posicion' => [
                    'timestamp' => $posiciones->last()->timestamp->toISOString(),
                    'coordenadas' => $posiciones->last()->coordenadas,
                ],
                'velocidad_promedio_kmh' => round($posiciones->avg('velocidad') ?? 0, 2),
                'velocidad_maxima_kmh' => round($posiciones->max('velocidad') ?? 0, 2),
                'precision_promedio_metros' => round($posiciones->avg('precision') ?? 0, 2),
                'altitud_minima_metros' => round($posiciones->min('altitud') ?? 0, 2),
                'altitud_maxima_metros' => round($posiciones->max('altitud') ?? 0, 2),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular estadísticas',
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
