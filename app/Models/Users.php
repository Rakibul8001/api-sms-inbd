<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Users extends Model
{
	
	protected $table = 'users';

	protected $fillable = [
        'name', 
        'email', 
        'password',
        'phone',
        'company',
        'root_user_id',
        'manager_id',
        'reseller_id',
        'address',
        'country',
        'city',
        'state',
        'created_from',
        'created_by',
        'status',
        'verified',
        'security_code',
        'phone_verified',
        'api_token'
    ];
}