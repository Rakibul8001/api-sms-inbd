<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{

    protected $table = 'resellers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 
        'email', 
        'password',
        'phone',
        'company',
        'root_user_id',
        'manager_id',
        'address',
        'country',
        'city',
        'state',
        'created_from',
        'created_by',
        'status',
        'verified'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function senderid()
    {
        return $this->hasOne(ResellerSender::class,'reseller_id','id');
    }


    public function senderids()
    {
        return $this->hasMany(ResellerSender::class, 'reseller_id','id');
    }

    public function documents() {
        return $this->hasMany(ResellerDocument::class,'reseller_id','id');
    }
}
