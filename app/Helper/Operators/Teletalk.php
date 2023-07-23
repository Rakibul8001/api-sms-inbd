<?php

namespace App\Helper\Operators;

use App\Helper\CpOperators\CpTelitalk;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use App\Models\SmsDeliveryReports;
use App\Models\SmsGatewayErrors;

use App\Models\SmsCampaignNumbersA;
use App\Models\SmsCampaignNumbersB;
use App\Models\SmsCampaignNumbersC;
use App\Models\SmsCampaignNumbersD;
use App\Models\SmsCampaignNumbersE;

class Teletalk
{

	//To send SMS text messages to one recipient
	public static function sendSingleMessage($smsSingle, $senderidgatewayInfo,$clientSenderid)
	{
		
		$gatewayinfo = $senderidgatewayInfo->info;
		

		if($smsSingle->is_unicode==1){
			$msgType = "3";
		} else {
			$msgType = "1";
		}


		$client = new Client([
	        'verify' => false
        ]);

		$phone = "880".$smsSingle->number;

		//if is_cp_on == 1 then send message through central platform
		if($gatewayinfo->is_cp_on == 1){
			try {

				$response = $client->post($gatewayinfo->operator->cp_single_url, [
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'json' => [
						"username"=> $gatewayinfo->username,
						"password"=> $gatewayinfo->password,
						"billMsisdn"=> $gatewayinfo->billMsisdn,
						"usernameSecondary"=> "" ,
						"passwordSecondary"=> "" ,
						"billMsisdnSecondary"=> "" ,
						"apiKey"=> "i1UPKvfdJxMXB7jYSNZNhsXji81P4HmW" ,
						"cli"=> $senderidgatewayInfo->senderid,
						// "cli"=> $clientSenderid,
						"msisdnList"=> [$phone],
						"transactionType"=> "T" ,
						"messageType"=> "1" ,
						"message"=> $smsSingle->content
					]
				]);
	  
			  } catch (RequestException $e) {
	  
				  $smsSingle->status = 3;
				  $smsSingle->save();
	  
				  SmsGatewayErrors::create([
					  'sms_id'				=> $smsSingle->id,
					  'type'				=> 1, //for single sms
					  'operator'			=> 5, // telitalk
					  'error_code'		=> 'Con Failed',
					  'error_description'	=> 'Connection Error',
				  ]);
	  
				  return 'Gateway Connection Error';
	  
			}

			//cpgp response 
			return CpTelitalk::sendSingleMessageResponse($response,$smsSingle);


		}else{
			
			try {

				$response = $client->post($gatewayinfo->operator->single_url, [
					  'form_params' => [ 
						  "username" 		=> $gatewayinfo->username,
						  "password" 		=> $gatewayinfo->password,
						  "apicode"  		=> "5",
						  "msisdn"   		=> "0".$smsSingle->number,
						  "countrycode" 	=> "880",
						//   "cli"      		=> $senderidgatewayInfo->senderid,
						  "cli"      		=> $clientSenderid,
						  "messagetype" 	=> $msgType,
						  "message"  		=> $smsSingle->content,
						  "messageid"   => "0"
					  ]
				  ]);
	  
			  } catch (RequestException $e) {
	  
				  $smsSingle->status = 3;
				  $smsSingle->save();
	  
				  SmsGatewayErrors::create([
					  'sms_id'				=> $smsSingle->id,
					  'type'				=> 1, //for single sms
					  'operator'			=> 5, // telitalk
					  'error_code'		=> 'Con Failed',
					  'error_description'	=> 'Connection Error',
				  ]);
	  
				  return 'Gateway Connection Error';
	  
			  }


		}


		// if is_cp_off response 

		$responseData = explode(",",$response->getBody());
		if ($responseData[1]) {
			if ($responseData[1]==200) {
				//msg sent successfully

				$smsSingle->status = 1;
				$smsSingle->save();

				SmsDeliveryReports::create([
					'sms_id'		=> $smsSingle->id,
					'type'			=> 1, //for single sms
					'operator'		=> 5, // telitalk
					'reference'		=> $responseData[2],
					'status'		=> 'Pending',
					'description'	=> '',
					'active'		=> 1,
				]);

				//sms sending successful
				return 'success';

			} else {
				//number failed with error code
										
				if (isset($responseData[2])) {
					$errorCode = $responseData[1];
					$errorDescription = $responseData[2];
				} else {
					$errorCode = 'GPError';
					$errorDescription = $responseData[1];
				}
				SmsGatewayErrors::create([
					'sms_id'				=> $smsSingle->id,
					'type'				=> 1, //for campaign sms
					'operator'			=> 5, // telitalk
					'error_code'		=> $errorCode,
					'error_description'	=> $errorDescription,
				]);

				$smsSingle->status = 3;
				$smsSingle->save();

				//process refund
				//sms sending failed
				return 'Gateway Operator Error';
			}
		} else {
			SmsGatewayErrors::create([
				'sms_id'				=> $smsSingle->id,
				'type'				=> 1, //for campaign sms
				'operator'			=> 5, // telitalk
				'error_code'		=> 'Unexpected',
				'error_description'	=> 'Unexpected Response',
			]);
			//unexpected response
			$smsSingle->status = 3;
			$smsSingle->save();

			//process refund
			//sms sending failed
			return 'Gateway Operator Error';
		}
		return false;
	}


	//To send same SMS to multiple recipients
	public static function sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus=0)
	{
		$batchLimit = 20;
		$numberPrefix = "880";

		//get the input operator numbers
		$inputOperator = $senderidgatewayInfo->input_operator;


		$campaignType = $campaign->campaign_type;
        if ($campaignType=='A') {
        	$operatorNumbers = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('operator', $inputOperator)->where('status', $numberStatus)->get();
        } else if ($campaignType=='B') {
            $operatorNumbers = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('operator', $inputOperator)->where('status', $numberStatus)->get();
        } else if ($campaignType=='C') {
            $operatorNumbers = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('operator', $inputOperator)->where('status', $numberStatus)->get();
        } else if ($campaignType=='D') {
            $operatorNumbers = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('operator', $inputOperator)->where('status', $numberStatus)->get();
        } else if ($campaignType=='E') {
            $operatorNumbers = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('operator', $inputOperator)->where('status', $numberStatus)->get();
        }

		$operatorTotalNumbers = count($operatorNumbers);

		if ($operatorTotalNumbers>0) {

			$gatewayinfo = $senderidgatewayInfo->info;

			if($campaign->is_unicode==1){
				$msgType = "3";
			} else {
				$msgType = "1";
			}

			$successCount = 0;
			$failedCount = 0;

			$numberCounter = 0;
			$numberString = '';

			$batchCount = 0;

			foreach ($operatorNumbers as $key => $operatorNumber) {
				
				//send sms one by one
				$client = new Client([
			        'verify' => false
		      ]);

		      $failedStatus = 0;
				try {

				  	$response = $client->post($gatewayinfo->operator->single_url, [
			            'form_params' => [
			            	"op"			=> "SMS",
			            	"user" 			=> $senderidgatewayInfo->username,
			            	"pass" 			=> $senderidgatewayInfo->password,
			            	"mobile"   		=> "880".$operatorNumber->number,
			            	"charset"   	=> "UTF-8",
			            	"sms"  			=> $campaign->content,
			            ]
			        ]);
		      } catch (RequestException $e) {

				  	$failedCount++;

				  	$operatorNumber->status = 3;
				  	$operatorNumber->save();
				  	SmsGatewayErrors::create([
						'sms_id'				=> $operatorNumber->id,
						'type'				=> 2, //for campaign sms
						'operator'			=> 5, // teletalk
						'error_code'		=> 'Con Failed',
						'error_description'	=> 'Connection Error',
					]);

				   $failedStatus = 1;
				}

				if($failedStatus==0){

					$use_errors = libxml_use_internal_errors(true);
					$xml_object= simplexml_load_string($response->getBody());
					if (false === $xml_object) {
					   //api returned unexpected response
						$failedCount++;

					  	$operatorNumber->status = 3;
					  	$operatorNumber->save();
					  	SmsGatewayErrors::create([
							'sms_id'				=> $operatorNumber->id,
							'type'				=> 2, //for campaign sms
							'operator'			=> 5, // teletalk
							'error_code'		=> 'Unexpected',
							'error_description'	=> 'Unexpected Response',
						]);

					  $failedStatus = 1;
					}
					libxml_clear_errors();
					libxml_use_internal_errors($use_errors);

				}

				if($failedStatus==0){
					
					$resultArr = explode(',', $xml_object);

					if (isset($resultArr[1])) {
					 	//format is ok
					 	
					 	$operator_smsid =  @$resultArr[1];
					 	if ($resultArr[0]=='SUCCESS') {
					 		//sms send successful

				        	$smsid = explode('=', $operator_smsid);

							$operatorNumber->status = 1;
							$operatorNumber->save();

							SmsDeliveryReports::create([
								'sms_id'		=> $operatorNumber->id,
								'type'			=> 2, //for campaign sms
								'campaign_type'	=> $campaignType,
								'operator'		=> 5, // teletalk
								'reference'		=> $smsid[1],
								'status'		=> 'Pending',
								'description'	=> 'Successfully sent',
								'active'		=> 1,
							]);

							$successCount++;

			        } else {
							//api returned error
							SmsGatewayErrors::create([
								'sms_id'			=> $operatorNumber->id,
								'type'				=> 2, //for campaign sms
								'operator'			=> 5, // teletalk
								'error_code'		=> 'Failed',
								'error_description'	=> $resultArr[0],
							]);

							$operatorNumber->status = 3;
							$operatorNumber->save();

							//process refund
							//sms sending failed
							$failedCount++;
						}
					} else {
						//invalid format
						$operatorNumber->status = 3;
					  	$operatorNumber->save();
					  	SmsGatewayErrors::create([
							'sms_id'				=> $operatorNumber->id,
							'type'				=> 2, //for campaign sms
							'operator'			=> 5, // teletalk
							'error_code'		=> 'Unexpected',
							'error_description'	=> 'Unexpected Response',
						]);

						$failedCount++;
					}			        
				}
 

			} //numbers processing end

			$data['successCount'] = $successCount;
			$data['failedCount'] = $failedCount;
			return $data;

		}

		// number of this operator

		return false;
	}

	//To retrieve the status of a sent message
	public static function getMessageStatus()
	{

		return true;
		
	}	
	
}