<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSenderTtk extends Model
{
    protected $table = 'sms_senders_ttk';
    protected $fillable = [
        'main_id',
        'status',
        'user',
        'password',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    public function createdBy()
    {
        return $this->belongsTo(RootUser::class, 'created_by','id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(RootUser::class, 'updated_by','id');
    }

}
