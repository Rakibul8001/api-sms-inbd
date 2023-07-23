<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLowcost extends Model
{
    protected $table = 'sms_lowcost';
    protected $fillable = [
        'campaign_id',
        'user_id',
        'number',
        'operator',
        'content',
        'is_unicode',
        'qty',
        'status',
        'route_id',
        'created_at',
        'updated_at',
        'active'
    ];
}
