<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SenderidMaster extends Model
{
    protected $table = 'senderid_master';
    protected $fillable = [
        'name',        
        'type',
        'description',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'active',
    ];

    public function senderidGateways()
    {
        return $this->hasMany(SenderidGateways::class,'master_senderid','id');
    }
}