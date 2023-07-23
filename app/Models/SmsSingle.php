<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSingle extends Model
{
    protected $table = 'sms_individuals';
    protected $fillable = [
        'sms_id',
        'user_id',
        'sender_id',
        'category',
        'number',
        'operator',
        'is_unicode',
        'content',
        'qty',
        'sent_through',
        'is_scheduled',
        'scheduled_time',
        'error_id',
        'status',
        'created_at',
        'updated_at',
        'active'
    ];
}
