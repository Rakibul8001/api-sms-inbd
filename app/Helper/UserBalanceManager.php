<?php

namespace App\Helper;

use App\Models\Users;


class UserBalanceManager
{

	public static function updateBalance($user, $category, $newBalance)
	{
		$userAC = Users::find($user);

		if (!$userAC) {
			return false;
		}

        if ($category==1) {
            $userAC->mask_balance = $newBalance;
            $userAC->save();
        } else if($category==2){
            $userAC->nonmask_balance = $newBalance;
            $userAC->save();
        } else if($category==3){
            $userAC->voice_balance = $newBalance;
            $userAC->save();
        }else if($category==4){
            $userAC->lowcost_balance = $newBalance;
            $userAC->save();
        }

        return true;
		
	}
}