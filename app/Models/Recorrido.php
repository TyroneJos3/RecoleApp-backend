<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recorrido extends Model
{
    use HasFactory;

    protected $table = 'recorridos';
    protected $fillable = [
        'recorrido_remoto_id',
        'ruta_id',
        'vehiculo_id',
        'conductor_id',
        'inicio',
        'fin',
        'distancia_total',
        'duracion',
        'puntos_totales',
        'activo',
        'datos_adicionales',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'distancia_total' => 'decimal:2',
        'duracion' => 'integer',
        'puntos_totales' => 'integer',
        'activo' => 'boolean',
        'datos_adicionales' => 'array',
    ];

    protected $hidden = [];
    protected $appends = ['duracion_formateada', 'velocidad_promedio'];


    public function posiciones()
    {
        return $this->hasMany(Posicion::class, 'recorrido_id', 'recorrido_remoto_id')
                    ->orderBy('timestamp', 'asc');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeFinalizados($query)
    {
        return $query->where('activo', false)->whereNotNull('fin');
    }

    public function scopePorConductor($query, $conductorId)
    {
        return $query->where('conductor_id', $conductorId);
    }

    public function scopePorVehiculo($query, $vehiculoId)
    {
        return $query->where('vehiculo_id', $vehiculoId);
    }

    public function scopePorRuta($query, $rutaId)
    {
        return $query->where('ruta_id', $rutaId);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('inicio', [$fechaInicio, $fechaFin]);
    }

    // duracion
    public function getDuracionFormateadaAttribute()
    {
        if (!$this->duracion) {
            return '00:00:00';
        }

        $horas = floor($this->duracion / 3600);
        $minutos = floor(($this->duracion % 3600) / 60);
        $segundos = $this->duracion % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }


    public function estaEnCurso()
    {
        return $this->activo && is_null($this->fin);
    }


    public function estaFinalizado()
    {
        return !$this->activo && !is_null($this->fin);
    }


    public function getVelocidadPromedioAttribute()
    {
        if (!$this->duracion || $this->duracion == 0) {
            return 0;
        }

        $horas = $this->duracion / 3600;

        if ($horas == 0) {
            return 0;
        }

        return round($this->distancia_total / $horas, 2);
    }


    public function getTotalPosicionesAttribute()
    {
        return $this->posiciones()->count();
    }


    public function getPrimeraPosicion()
    {
        return $this->posiciones()->orderBy('timestamp', 'asc')->first();
    }


    public function getUltimaPosicion()
    {
        return $this->posiciones()->orderBy('timestamp', 'desc')->first();
    }


    public function finalizar($distanciaTotal, $duracion, $puntosTotales)
    {
        return $this->update([
            'fin' => now(),
            'distancia_total' => $distanciaTotal,
            'duracion' => $duracion,
            'puntos_totales' => $puntosTotales,
            'activo' => false,
        ]);
    }


    public function calcularDuracionReal()
    {
        $primera = $this->getPrimeraPosicion();
        $ultima = $this->getUltimaPosicion();

        if (!$primera || !$ultima) {
            return 0;
        }

        return $ultima->timestamp->diffInSeconds($primera->timestamp);
    }


    public function getResumen()
    {
        return [
            'recorrido_id' => $this->recorrido_remoto_id,
            'conductor_id' => $this->conductor_id,
            'vehiculo_id' => $this->vehiculo_id,
            'ruta_id' => $this->ruta_id,
            'inicio' => $this->inicio->format('Y-m-d H:i:s'),
            'fin' => $this->fin ? $this->fin->format('Y-m-d H:i:s') : null,
            'duracion' => $this->duracion_formateada,
            'distancia_km' => $this->distancia_total,
            'velocidad_promedio_kmh' => $this->velocidad_promedio,
            'total_posiciones' => $this->puntos_totales,
            'estado' => $this->activo ? 'En curso' : 'Finalizado',
        ];
    }
}
