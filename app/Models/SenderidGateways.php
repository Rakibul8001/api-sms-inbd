<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SenderidGateways extends Model
{
    protected $table = 'senderid_gateways';
    
    protected $fillable = [
        'master_senderid',
        'input_operator',
        'output_operator',
        'senderid',
        'gateway',
        'username',
        'password',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'active',
    ];

    public function info()
    {
        return $this->belongsTo(OperatorGateways::class,'gateway','id');
    }

}