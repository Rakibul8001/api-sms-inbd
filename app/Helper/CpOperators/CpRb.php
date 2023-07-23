<?php

namespace App\Helper\CpOperators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use App\Models\SmsDeliveryReports;
use App\Models\SmsGatewayErrors;

use App\Models\SmsCampaignNumbersA;
use App\Models\SmsCampaignNumbersB;
use App\Models\SmsCampaignNumbersC;
use App\Models\SmsCampaignNumbersD;
use App\Models\SmsCampaignNumbersE;


class CpRb
{
    public static function sendSingleMessageResponse($response,$smsSingle){

        $responseData = json_decode($response->getBody());
	
			if ($responseData->serverResponseCode ==9000) {
				//msg sent successfully

				$smsSingle->status = 1;
				$smsSingle->save();

				SmsDeliveryReports::create([
					'sms_id'		=> $smsSingle->id,
					'type'			=> 1, //for single sms
					'operator'		=> 4, // Robi
					'reference'		=> '',
					'status'		=> 'Pending',
					'description'	=> '',
					'active'		=> 1,
				]);

				//sms sending successful
				return 'success';

			} else {
				//number failed with error code
										
				SmsGatewayErrors::create([
					'sms_id'				=> $smsSingle->id,
					'type'				=> 1, //for campaign sms
					'operator'			=> 4, // Robi
					'error_code'		=> '',
					'error_description'	=> '',
				]);

				$smsSingle->status = 3;
				$smsSingle->save();

				//process refund
				//sms sending failed
				return 'Gateway Operator Error';
			}
	

    }
}