<?php

namespace App\Helper\Permission;

use App\Models\Admin;

use App\Models\AdminRole;
use App\Models\AdminRolePermission;


class AccessControl
{

	public static function getPermission($resource)
	{

		$user = Admin::find($_SESSION['user']);


		if ($user) {
			
			$permission = AdminRolePermission::where('role_id', $user->role)->where('resource', $resource)->first();

			if ($permission) {
				return true;
			}

		}

		return false;
		
	}	
	
}