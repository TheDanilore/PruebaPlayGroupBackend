<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variacion extends Model
{
    use HasFactory;

    protected $table = 'variaciones';

    protected $fillable = [
        'color_id',
        'longitud_id',
        'tamano_id'
    ];


    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function longitud()
    {
        return $this->belongsTo(Longitud::class, 'longitud_id');
    }

    public function tamano()
    {
        return $this->belongsTo(Tamano::class, 'tamano_id');
    }
}
