<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSender extends Model
{
    protected $fillable = [
        'user_id',
        'sms_sender_id',
        'status',
        'default',
        'created_by',
        'updated_by',
        'user_type'
    ];

    public function senderClients()
    {
        return $this->belongsTo(SmsSender::class,'sms_sender_id','id');
    }

    public function client()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
