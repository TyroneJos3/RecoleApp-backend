<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehiculo_id',
        'placa',
        'marca',
        'modelo',
        'activo',
        'perfil_id',
        'current_driver_id',
        'user_id',
        'status',
        'capacity',
        'year'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    public function currentDriver()
    {
        return $this->belongsTo(User::class, 'current_driver_id');
    }


    public function conductorActual()
    {
        return $this->currentDriver();
    }


    public function assignments()
    {
        return $this->hasMany(Asignacion::class, 'vehicle_id');
    }


    public function asignaciones()
    {
        return $this->assignments();
    }


    public function activeAssignment()
    {
        return $this->hasOne(Asignacion::class, 'vehicle_id')
                    ->where('status', 'active')
                    ->whereNull('unassignment_date')
                    ->latest();
    }


    public function asignacionActiva()
    {
        return $this->activeAssignment();
    }


    public function isAssigned(): bool
    {
        return !is_null($this->current_driver_id);
    }


    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }


    public function scopeAvailable($query)
    {
        return $query->where('activo', true)
                     ->whereNull('current_driver_id');
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('current_driver_id');
    }


    public function scopeForProfile($query, $profileId)
    {
        return $query->where('perfil_id', $profileId);
    }
}
