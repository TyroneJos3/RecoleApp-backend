<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehiculo extends Model
{
    use HasFactory;

    protected $filable = [
        'vehiculo_id',
        'placa',
        'marca',
        'modelo',
        //'activo',
        'user_id',
        'perfil_id'
    ];

   // protected $casts = [
   //     'activo' => 'boolean',
   // ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
