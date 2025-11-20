<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asignacion extends Model
{
    use HasFactory;

    protected $table = 'asignacion_vehiculo';

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'assignment_date',
        'unassignment_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'assignment_date' => 'datetime',
        'unassignment_date' => 'datetime',
    ];


    public function vehicle()
    {
        return $this->belongsTo(Vehiculo::class, 'vehicle_id');
    }


    public function vehiculo()
    {
        return $this->vehicle();
    }


    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }


    public function conductor()
    {
        return $this->driver();
    }


    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->whereNull('unassignment_date');
    }


    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }


    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }


    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }
}
