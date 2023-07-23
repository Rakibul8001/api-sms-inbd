<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AdminSecurity extends Model
{
	
	protected $table = 'admin_security';

	protected $fillable = [

		'user',
		'question',
		'answer',
		'created_at',
		'updated_at',
		'active'
	];
}