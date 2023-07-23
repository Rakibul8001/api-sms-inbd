<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operators extends Model
{
    protected $fillable = [
        'name',
        'prefix',
        'type',
        'single_url',
        'multi_url',
        'delivery_url',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'active',
    ];

    public function operatorGateways()
    {
        return $this->hasMany(OperatorGateways::class,'operator_id','id');
    }
}