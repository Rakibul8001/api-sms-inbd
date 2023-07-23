<?php

namespace App\Helper\Operators;

use GuzzleHttp\Client;
use App\Helper\CpOperators\CpGp;

use App\Models\SmsGatewayErrors;
use App\Models\SmsDeliveryReports;

use App\Models\SmsCampaignNumbersA;
use App\Models\SmsCampaignNumbersB;
use App\Models\SmsCampaignNumbersC;
use App\Models\SmsCampaignNumbersD;
use App\Models\SmsCampaignNumbersE;
use GuzzleHttp\Exception\RequestException;


class GP
{

    //To send SMS text messages to one recipient
	public static function sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid)
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
					  'operator'			=> 1, // gp
					  'error_code'		=> 'Con Failed',
					  'error_description'	=> 'Connection Error',
				  ]);
	  
				  return 'Gateway Connection Error';
	  
			}

			//cpgp response 
			return CpGp::sendSingleMessageResponse($response,$smsSingle);


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
					  'operator'			=> 1, // gp
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
					'operator'		=> 1, // gp
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
					'operator'			=> 1, // gp
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
				'operator'			=> 1, // gp
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
	//To send same SMS to multiple recipients
	public static function sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus=0)
	{
		$batchLimit = 15;
		$numberPrefix = "0";

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
			$campaignNumbers = array();
			foreach ($operatorNumbers as $key => $operatorNumber) {
				
				$numberCounter++;
				
				if ($numberCounter < $batchLimit) {
					if ($operatorTotalNumbers>1) {
						$numberString .= $numberPrefix.$operatorNumber->number;
						if (isset($operatorNumbers[$key+1])) {
							$numberString .= ',';
						}
					}
					
					$batchCount++;
					array_push($campaignNumbers, $operatorNumber);
				}

				if ($numberCounter== $batchLimit || $key == $operatorTotalNumbers-1) {
					
					//to prevent number duplication in the last(ending) number in a collection we apply this condition
					if(substr($numberString, -1)==','){
						$numberString .= $numberPrefix.$operatorNumber->number;

						$batchCount++;
						array_push($campaignNumbers, $operatorNumber);
					}
					if ($numberString=='') {
						$numberString .= $numberPrefix.$operatorNumber->number;
						array_push($campaignNumbers, $operatorNumber);
					}
					//condition end

					//numbers are ready for sending to operator

					$client = new Client([
				        'verify' => false
			        ]);

					$failedStatus = 0;

					// $phone = "88".$numberPrefix.$operatorNumber->number;

					foreach($operatorNumbers as $key=>$operator){
						$failedStatus = 0;

						$phone = "880".$operator->number;
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
									"msisdnList"=> [$phone],
									"transactionType"=> "T" ,
									"messageType"=> "1" ,
									"message"=> $campaign->content
								]
							]);

	
						} catch (RequestException $e) {
							
								$failedCount += $batchCount;
								$batchCount = 0;
								  
								  //gateway connection error occured
								// foreach ($campaignNumbers as $campNum) {
									//campNum is number object
									// $campaign->status = 3;
									// $campaign->save();
	
									SmsGatewayErrors::create([
										'sms_id'					=> $campaign->id,
										'type'					=> 2, //for campaign sms
										'operator'				=> 1, // gp
										'error_code'			=> 'BatchError',
										'error_description'	=> 'Connection Error',
									]);
								// }
	
								$failedStatus = 1;
						}

						//test with successful----------------------------------------------------------------
	
						if ($failedStatus == 0) {
	
							$responseData = json_decode($response->getBody());

							if($responseData->serverResponseCode ==9000){

								SmsDeliveryReports::create([
									'sms_id'			=> $operator->id,
									'type'			=> 2, //for campaign sms
									'campaign_type'	=> $campaignType,
									'operator'		=> 1, // gp
									'reference'		=> "none",
									'status'		=> 'Pending',
									'description'	=> '',
									'active'		=> 1,
								]);
								//sms sending successful
								$operator->status= 1;
								$operator->save();
							
								$successCount++;
							}else {
								//number failed with error code
				
								$errorCode = 'GPError';
								$errorDescription = "Error occurred while sending";
							
								SmsGatewayErrors::create([
									'sms_id'				=> $operator->id,
									'type'				=> 2, //for campaign sms
									'operator'			=> 1, // gp
									'error_code'		=> $errorCode,
									'error_description'	=> $errorDescription,
								]);
								$operator->status= 3;
								$operator->save();

								$failedCount++;
							}
						




							// $returnArr = explode("\n", $response->getBody());
	
						
	
						}		
	
	
						// end test ----------------------------------------------------------------

						//unset
						$campaignNumbers = array();
	
						$numberCounter = 0;
						$numberString = '';
	
						// usleep(750000); // .75 second
						
					}

				} //batch end here
				

			} //numbers processing end


			$data['successCount'] = $successCount;
			$data['failedCount'] = $failedCount;
			return $data;

		}

		// number of this operator

		return false;
	}

	//To retrieve the status of a sent message
	public static function getMessageStatus($number, $senderidgatewayInfo, $msgType, $messageid)
	{

		$gatewayinfo = $senderidgatewayInfo->info;

		$client = new Client([
	        'verify' => false
        ]);

		try {

		  $response = $client->post($gatewayinfo->operator->delivery_url, [
	            'form_params' => [ 
	                "username" 		=> $gatewayinfo->username,
	                "password" 		=> $gatewayinfo->password,
	                "apicode"  		=> "4", // for delivery report
	                "msisdn"   		=> "0".$number,
	                "countrycode" 	=> "0",
	                "cli"      		=> $senderidgatewayInfo->senderid,
	                "messagetype" 	=> $msgType,
	                "message"  		=> "0",
	                "messageid"     => $messageid
	            ]
	        ]);

		} catch (RequestException $e) {

		  return false;

		}

		$responseData = explode(",",$response->getBody());
		if ($responseData[0]==200) {

            $succesData = explode("#",$responseData[1]);

            if (isset($succesData[1])) {
                if ($succesData[1]=='Delivered') {
                
                    return 2; // 2 means delivered
                
                } else if ($succesData[1]=='UnDelivered') {
                    
                    return 4; // 4 means UnDelivered
                
                }
            }
        }

		return false;
		
	}		
	
}