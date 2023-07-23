<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AdminLogin extends Model
{
	
	protected $table = 'admin_login';

	protected $fillable = [

		'user',
		'login_time',
		'logout_time',
		'active'
	];
}