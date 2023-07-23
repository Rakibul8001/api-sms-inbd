<?php

namespace App\Auth;

use App\Models\Users;
use App\Models\Modem;
use App\Models\AdminLogin;


class Auth
{

	public function user($api_token)
	{
		if ($userId = $this->check($api_token)) {
			return Users::find($userId);
		}
		
	}

	public function check($api_token)
	{
		$userId = $this->attemp($api_token);

		if (is_numeric($userId)) {
			return $userId;
		}

		return false;
	}

	public static function attemp($api_token)
	{
		// echo $api_token; die();
		//for internal bypass
		if ($api_token=='2021RrrDTHstDkRnVFkPlPrurhT611DthstInternalAPI') {
			return 10;
		}


		//for users
		$user = Users::where('api_token', $api_token)->first();

		if (!$user) {
			//error
			$error = 'Authentication Error!';
			return $error;
		}

		if ($user->status=='y') {
			//success
			return $user->id;
		} else {
		    echo 'We have found unethical transection from your account, your account is blocked until the issue is solve. Please contact support team.';
		    exit;
		}

		//error
		$error = 'user disabled';

		return $error;
	}

	public static function attempModem($api_token)
	{
		//for modem
		$modem = Modem::where('api_token', $api_token)->first();

		if (!$modem) {
			//error
			$error = 'Authentication Error!';
			return $error;
		}

		if ($modem->active==1) {
			//success
			return $modem->id;
		}

		//error
		$error = 'modem disabled';

		return $error;
	}
	
	public function modem($api_token)
	{
		//for modem
		$modem = Modem::where('api_token', $api_token)->first();

		if (!$modem) {
			//error
			$error = 'Authentication Error!';
			return $error;
		}
		return $modem;
		
	}
}