<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecorridoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recorrido_remoto_id' => $this->recorrido_remoto_id,
            'ruta_id' => $this->ruta_id,
            'vehiculo_id' => $this->vehiculo_id,
            'conductor_id' => $this->conductor_id,
            'inicio' => $this->inicio?->toISOString(),
            'fin' => $this->fin?->toISOString(),
            'distancia_total' => (float) $this->distancia_total,
            'duracion' => (int) $this->duracion,
            'puntos_totales' => (int) $this->puntos_totales,
            'activo' => (bool) $this->activo,
            'velocidad_promedio' => $this->velocidad_promedio,
            'duracion_formateada' => $this->duracion_formateada,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Solo incluir posiciones si se solicitaron explícitamente
            'posiciones' => PosicionResource::collection($this->whenLoaded('posiciones')),
        ];
    }
}
