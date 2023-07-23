<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorGateways extends Model
{
    protected $table = 'operator_gateways';
    protected $fillable = [
        'operator_id',
        'name',
        'username',
        'password',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'active',
    ];

    public function operator()
    {
        return $this->belongsTo(Operators::class,'operator_id', 'id');
    }
}
