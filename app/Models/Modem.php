<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modem extends Model
{
    protected $table = "modems";
    protected $fillable = [
        'name',
        'sim_number',
        'description',
        'status',
        'api_token',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'active',
    ];

}