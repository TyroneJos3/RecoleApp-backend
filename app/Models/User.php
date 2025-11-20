<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'driver_license',
        'current_vehicle_id',
        'is_active_driver'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active_driver' => 'boolean'
    ];

    /**
     * Relación con el vehículo actual asignado
     */
    public function currentVehicle()
    {
        return $this->belongsTo(Vehiculo::class, 'current_vehicle_id');
    }

    /**
     * Relación con las asignaciones como conductor
     */
    public function vehicleAssignments()
    {
        return $this->hasMany(Asignacion::class, 'driver_id');
    }

    /**
     * Verificar si el usuario es admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verificar si el usuario es conductor
     */
    public function isConductor(): bool
    {
        return $this->role === 'conductor';
    }

    /**
     * Scope para conductores activos
     */
    public function scopeDrivers($query)
    {
        return $query->where('role', 'conductor')->where('is_active_driver', true);
    }

    /**
     * Scope para conductores disponibles sin vehículo asignado
     */
    public function scopeAvailableDrivers($query)
    {
        return $query->where('role', 'conductor')->whereNull('current_vehicle_id');
    }
}
