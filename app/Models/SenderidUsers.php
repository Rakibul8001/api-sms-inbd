<?php

namespace App\Models;

use App\Models\SenderidMaster;
use Illuminate\Database\Eloquent\Model;

class SenderidUsers extends Model
{
    protected $table = 'senderid_users';
    protected $fillable = [
        'senderid',
        'user',
        'user_type',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'active',
    ];

    public function getUsersOfSenderid()
    {
        return $this->hasMany(User::class,'senderid','id');
    }

    public function getUserOfSenderid()
    {
        return $this->belongsTo(User::class,'user','id');
    }

    public function getSenderid()
    {
        return $this->belongsTo(SenderidMaster::class,'senderid', 'id');
    }

}