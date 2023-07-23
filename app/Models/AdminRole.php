<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AdminRole extends Model
{
	
	protected $table = 'admin_role';

	protected $fillable = [

		'name',
		'description',
		'created_at',
		'updated_at',
		'created_by',
		'active'

	];
}