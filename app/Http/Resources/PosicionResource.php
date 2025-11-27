<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosicionResource extends JsonResource
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
            'recorrido_id' => $this->recorrido_id,
            'lat' => (float) $this->lat,
            'lon' => (float) $this->lon,
            'timestamp' => $this->timestamp?->toISOString(),
            'altitud' => $this->altitud ? (float) $this->altitud : null,
            'precision' => $this->precision ? (float) $this->precision : null,
            'velocidad' => $this->velocidad ? (float) $this->velocidad : null,
            'rumbo' => $this->rumbo ? (float) $this->rumbo : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
