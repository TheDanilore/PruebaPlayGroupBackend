<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventario';

    protected $fillable = [
        'producto_id',
        'variacion_id',
        'precio_unitario',
        'cantidad'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variacion()
    {
        return $this->belongsTo(Variacion::class, 'variacion_id');
    }

}
