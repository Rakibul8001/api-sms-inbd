<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AccessTracker extends Model
{
	
	protected $table = 'access_tracker';

	protected $fillable = [

		'user',
		'ip',
		'uri',
		'method',
		'created_at',
	];
}