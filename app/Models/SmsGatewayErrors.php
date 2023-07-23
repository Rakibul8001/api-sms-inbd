<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsGatewayErrors extends Model
{
    protected $table = 'sms_gateway_errors';
    
    protected $fillable = [
        'sms_id',
        'type',
        'operator',
        'error_code',
        'error_description',
        'created_at',
        'updated_at',
    ];

}