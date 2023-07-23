<?php

namespace App\Helper\Includes;

class AddedFunctions
{

	public static function taka_format($amount)
	{
		if (!$amount) {
			return "0.00";
		}

		$sign = "";

		if ($amount<0) {

	        $sign = "-";

	        $amount = sprintf('%0.2f', abs($amount));
	     
	     }

		$tmp = explode(".",$amount); // for float or double values
		$strMoney = "";
		$divide = 1000;
		$amount = $tmp[0];
		$strMoney .= str_pad($amount%$divide,3,"0",STR_PAD_LEFT);
		$amount = (int)($amount/$divide);
		
		while($amount>0)
		{
			$divide = 100;
			$strMoney = str_pad($amount%$divide, 2,"0",STR_PAD_LEFT).",".$strMoney;
			$amount = (int)($amount/$divide);
		}

		if(substr($strMoney, 0, 1) == "0")
		$strMoney = substr($strMoney,1);

		if(isset($tmp[1])) // if float and double add the decimal digits here.
		{
			return $sign .$strMoney.".".$tmp[1];
		}
		
		return $sign . $strMoney;
		
	}	
	
}