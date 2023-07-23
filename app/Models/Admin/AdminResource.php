<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;


class AdminResource extends Model
{
	
	protected $table = 'admin_resources';

	protected $fillable = [

		'module',
		'lebel',
		'level',
		'resource',
		'has_child',
		'parent',
		'module_pos'

	];
}