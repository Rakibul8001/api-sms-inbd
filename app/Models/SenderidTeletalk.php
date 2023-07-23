<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SenderidTeletalk extends Model
{
    protected $fillable = [
        'senderid_gatewayid',
        'username',
        'password',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'active',
    ];

}