<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posicion extends Model
{
    use HasFactory;


    protected $table = 'posiciones';
    protected $fillable = [
        'recorrido_id',
        'lat',
        'lon',
        'timestamp',
        'altitud',
        'precision',
        'velocidad',
        'rumbo',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lon' => 'decimal:8',
        'timestamp' => 'datetime',
        'altitud' => 'decimal:2',
        'precision' => 'decimal:2',
        'velocidad' => 'decimal:2',
        'rumbo' => 'decimal:2',
    ];


    protected $hidden = [];
    protected $appends = ['coordenadas', 'formato_display'];


    public function recorrido()
    {
        return $this->belongsTo(Recorrido::class, 'recorrido_id', 'recorrido_remoto_id');
    }


    public function scopePorRecorrido($query, $recorridoId)
    {
        return $query->where('recorrido_id', $recorridoId);
    }


    public function scopeOrdenadoPorTiempo($query)
    {
        return $query->orderBy('timestamp', 'asc');
    }

    public function scopeEntreTiempos($query, $inicio, $fin)
    {
        return $query->whereBetween('timestamp', [$inicio, $fin]);
    }


    public function scopeConBuenaPrecision($query, $metrosMaximos = 50)
    {
        return $query->where('precision', '<=', $metrosMaximos);
    }

    public function getCoordenadasAttribute()
    {
        return [
            'lat' => (float) $this->lat,
            'lon' => (float) $this->lon,
        ];
    }

    public function getGeoJsonAttribute()
    {
        return [
            'type' => 'Point',
            'coordinates' => [(float) $this->lon, (float) $this->lat],
        ];
    }


    public function distanciaA(Posicion $otraPosicion)
    {
        $radioTierra = 6371;

        $latFrom = deg2rad($this->lat);
        $lonFrom = deg2rad($this->lon);
        $latTo = deg2rad($otraPosicion->lat);
        $lonTo = deg2rad($otraPosicion->lon);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($radioTierra * $c, 3);
    }

    public function esValida()
    {
        return $this->lat >= -90 &&
               $this->lat <= 90 &&
               $this->lon >= -180 &&
               $this->lon <= 180;
    }


    public function tieneBuenaPrecision($metrosMaximos = 50)
    {
        if (is_null($this->precision)) {
            return false;
        }

        return $this->precision <= $metrosMaximos;
    }

    public function getFormatoDisplayAttribute()
    {
        return sprintf(
            'Lat: %s, Lon: %s',
            number_format($this->lat, 6),
            number_format($this->lon, 6)
        );
    }

    public function getGoogleMapsUrl()
    {
        return sprintf(
            'https://www.google.com/maps?q=%s,%s',
            $this->lat,
            $this->lon
        );
    }


    public function getPosicionAnterior()
    {
        return Posicion::where('recorrido_id', $this->recorrido_id)
            ->where('timestamp', '<', $this->timestamp)
            ->orderBy('timestamp', 'desc')
            ->first();
    }


    public function getPosicionSiguiente()
    {
        return Posicion::where('recorrido_id', $this->recorrido_id)
            ->where('timestamp', '>', $this->timestamp)
            ->orderBy('timestamp', 'asc')
            ->first();
    }


    public function getTiempoDesdeAnterior()
    {
        $anterior = $this->getPosicionAnterior();

        if (!$anterior) {
            return null;
        }

        return $this->timestamp->diffInSeconds($anterior->timestamp);
    }


    public function getDistanciaDesdeAnterior()
    {
        $anterior = $this->getPosicionAnterior();

        if (!$anterior) {
            return null;
        }

        return $this->distanciaA($anterior);
    }


    public function getInfo()
    {
        return [
            'id' => $this->id,
            'recorrido_id' => $this->recorrido_id,
            'coordenadas' => [
                'lat' => (float) $this->lat,
                'lon' => (float) $this->lon,
            ],
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'altitud' => $this->altitud,
            'precision_metros' => $this->precision,
            'velocidad_kmh' => $this->velocidad,
            'rumbo_grados' => $this->rumbo,
            'google_maps_url' => $this->getGoogleMapsUrl(),
            'es_valida' => $this->esValida(),
            'buena_precision' => $this->tieneBuenaPrecision(),
        ];
    }


    public function toLeaflet()
    {
        return [
            (float) $this->lat,
            (float) $this->lon,
        ];
    }
}
