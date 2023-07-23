<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSender extends Model
{
    protected $table = 'sms_senders';
    protected $fillable = [
        'sender_name',
        'operator_id',
        'status',
        'default',
        'user',
        'password',
        'gateway_info',
        'rotation_gateway_info',
        'created_by',
        'updated_by'
    ];

    public function createdBy()
    {
        return $this->belongsTo(RootUser::class, 'created_by','id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(RootUser::class, 'updated_by','id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class,'operator_id','id');
    }

}
