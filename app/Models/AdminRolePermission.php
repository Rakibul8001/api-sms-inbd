<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AdminRolePermission extends Model
{
	
	protected $table = 'admin_role_permission';

	protected $fillable = [

		'role_id',
		'resource',
		'permission',
		'created_at',
		'updated_at',
		'active'

	];
}