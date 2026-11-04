<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $table = 'wilayah';

    protected $fillable = ['nama', 'poligon', 'status_awas', 'awas_sumber', 'awas_sejak'];

    protected $casts = ['poligon' => 'array', 'status_awas' => 'boolean', 'awas_sejak' => 'datetime'];
}
