<?php

namespace App\Helper;

use App\Models\Users;
use App\Models\AdminLogin;
use App\Models\AdminRole;


class AdminDetails
{

	public static function getAdminName($id)
	{
		if (isset($_SESSION['user'])) {

			return Users::find($id)->name;
		
		} else return 0;
		
	}
	
}