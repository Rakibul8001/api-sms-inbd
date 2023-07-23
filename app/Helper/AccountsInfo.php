<?php

namespace App\Helper;

use App\Models\Accounts\AccountDailyCash;


class AccountsInfo
{

	public static function isDailyCashOpened()
	{

		$dailyCash = AccountDailyCash::where('date', date('Y-m-d'))->first();

		if ($dailyCash) {
			
			return true;
		}

		return false;
		
	}	
	
}