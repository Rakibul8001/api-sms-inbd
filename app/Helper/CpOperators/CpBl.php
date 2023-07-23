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


class CpBl
{
    public static function sendSingleMessageResponse($response,$smsSingle){

        $responseData = json_decode($response->getBody());
	
			if ($responseData->serverResponseCode ==9000) {
				//msg sent successfully
				$smsSingle->status = 1;
				$smsSingle->save();

				//no delivery report for Banglalink

				//sms sending successful
				return 'success';

			} else {

				$smsSingle->status = 3;
				$smsSingle->save();

				SmsGatewayErrors::create([
					'sms_id'				=> $smsSingle->id,
					'type'				=> 1, //for campaign sms
					'operator'			=> 2, // bl
					'error_code'		=> 'Failed',
					'error_description'	=> 'Sms Failed',
				]);
				return 'SMS sending failed';
			}
	

    }
}