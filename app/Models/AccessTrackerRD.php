<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AccessTrackerRD extends Model
{
	
	protected $table = 'aa_tracker';

	protected $fillable = [

		'user',
		'ip',
		'uri',
		'method',
		'created_at',
	];
}