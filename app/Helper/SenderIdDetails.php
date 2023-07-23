<?php

namespace App\Helper;

use App\Models\Users;
use App\Models\SenderidMaster;
use App\Models\SenderidUsers;
use App\Models\SenderidGateways;


class SenderIdDetails
{

	public static function getSenderIdByNameIfValid($clientId, $senderIdName)
	{
		$senderId = SenderidMaster::where('name', $senderIdName)->first();

		if ($senderId) {
			$senderIdUser = SenderidUsers::where('user', $clientId)->where('senderid', $senderId->id)->first();

			if ($senderIdUser) {
				return $senderId;
			}
		
		}

		return false;
	}


	public static function getOutputOperatorGatewayByInput($senderid, $inputOperator)
	{
		$senderidgatewayInfo = SenderidGateways::where('master_senderid', $senderid)->where('input_operator', $inputOperator)->first();

		if ($senderidgatewayInfo) {
			return $senderidgatewayInfo;
		}
		return false;
	}

	// public static function operatorGatewayOfSenderid($senderid, $inputOperator)
	// {
	// 	return SenderidGateways::where('master_senderid', $senderid)->get();
	// }
}