<?php

namespace App\Helper\Operators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use App\Models\SmsDeliveryReports;
use App\Models\SmsGatewayErrors;

use App\Models\SmsCampaignNumbersA;
use App\Models\SmsCampaignNumbersB;
use App\Models\SmsCampaignNumbersC;
use App\Models\SmsCampaignNumbersD;
use App\Models\SmsCampaignNumbersE;


class Robi
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

		try {

		  	$response = $client->get($gatewayinfo->operator->single_url, [
	            'query' => [ 
	                "Username" 		=> $gatewayinfo->username,
	                "Password" 		=> $gatewayinfo->password,
	                // "From"  		=> $senderidgatewayInfo->senderid,
	                "From"  		=> $clientSenderid,
	                "To"   			=> "880".$smsSingle->number,
	                "Message"  		=> $smsSingle->content,
	            ]
	        ]);

	  	}  catch (RequestException $e) {

		  	$smsSingle->status = 3;
			$smsSingle->save();

			SmsGatewayErrors::create([
				'sms_id'				=> $smsSingle->id,
				'type'				=> 1, //for single sms
				'operator'			=> 4, // robi
				'error_code'		=> 'Con Failed',
				'error_description'	=> 'Connection Error',
			]);

			return 'Gateway Connection Error';

		}

		$use_errors = libxml_use_internal_errors(true);
		$xml_object = simplexml_load_string($response->getBody());
		if (false === $xml_object) {
			//api returned error
			$smsSingle->status = 3;
			$smsSingle->save();
			SmsGatewayErrors::create([
				'sms_id'				=> $smsSingle->id,
				'type'				=> 1, //for single sms
				'operator'			=> 4, // robi
				'error_code'		=> 'Unexpected',
				'error_description'	=> 'Unexpected Response',
			]);

			return 'Gateway Error';

		}
		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		$responseData = json_decode(json_encode($xml_object),true);

		///start
		//check if response format is ok
		if (isset($responseData['ServiceClass'])) {
			
			

			if ($responseData['ServiceClass']['ErrorCode']==0) {

				//msg sent successfully

				//msg sent successfully
				$smsSingle->status = 1;
				$smsSingle->save();
				SmsDeliveryReports::create([
					'sms_id'		=> $smsSingle->id,
					'type'			=> 1, //for single sms
					'operator'		=> 4, // robi
					'reference'		=> $responseData['ServiceClass']['MessageId'],
					'status'		=> $responseData['ServiceClass']['Status'],
					'description'	=> $responseData['ServiceClass']['StatusText'],
					'active'		=> 1,
				]);

				//sms sending successful
				return 'success';

				

			} else {
				//api returned error

				SmsGatewayErrors::create([
					'sms_id'				=> $smsSingle->id,
					'type'				=> 1, //for campaign sms
					'operator'			=> 4, // robi
					'error_code'		=> $responseData['ServiceClass']['ErrorCode'],
					'error_description'	=> $responseData['ServiceClass']['ErrorText'],
				]);

				//process refund
				//sms sending failed
				$smsSingle->status= 3;
				$smsSingle->save();
				return 'Gateway Error';
				
			}
		} else {
			//api returned error

			SmsGatewayErrors::create([
				'sms_id'				=> $smsSingle->id,
				'type'				=> 1, //for campaign sms
				'operator'			=> 4, // robi
				'error_code'		=> 'Unexpected',
				'error_description'	=> 'Unexpected Response',
			]);

			//process refund
			//sms sending failed

			$smsSingle->status= 3;
			$smsSingle->save();
			return 'Gateway Error';
		}
		// end

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

					if ($operatorTotalNumbers>1) {
						try {

							$response = $client->get($gatewayinfo->operator->multi_url, [
						            'query' => [ 
						                "Username" 		=> $gatewayinfo->username,
						                "Password" 		=> $gatewayinfo->password,
						                "From"  		=> $senderidgatewayInfo->senderid,
						                "To"   			=> $numberString,
						                "Message"  		=> $campaign->content,
						            ]
						        ]);

						}  catch (RequestException $e) {
							
							$failedCount += $batchCount;

							$failedStatus = 1;
						  	
						  	//gateway connection error occured
							//set batch error
							Robi::setBatchErrorStatus($campaignNumbers, 'Connection Error');
						}

						if($failedStatus==0){

							$use_errors = libxml_use_internal_errors(true);
							$xml_object = simplexml_load_string($response->getBody());
							if (false === $xml_object) {
							  //api returned unexpected response
								$failedCount += $batchCount;

								//set batch error
								Robi::setBatchErrorStatus($campaignNumbers, 'Unexpected Response');
								$failedStatus = 1;
							}
							libxml_clear_errors();
							libxml_use_internal_errors($use_errors);

						}

				  		
		        		if($failedStatus==0){
		        			$responseArray = json_decode(json_encode($xml_object),true);
				    		//check if response format is ok
				    		if (isset($responseArray['ServiceClass'])) {
				    			foreach ($responseArray['ServiceClass'] as $keyOfNumber => $responseData) {

			        				$campaignMobileNumber = $campaignNumbers[$keyOfNumber];

									if ($responseData['ErrorCode']=='0') {
										//msg sent successfully

										SmsDeliveryReports::create([
											'sms_id'			=> $campaignMobileNumber->id,
											'type'			=> 2, //for campaign sms
											'campaign_type'	=> $campaignType,
											'operator'		=> 4, // robi
											'reference'		=> $responseData['MessageId'],
											'status'			=> $responseData['Status'],
											'description'	=> $responseData['StatusText'],
											'active'			=> 1,
										]);

										//sms sending successful
										$campaignMobileNumber->status= 1;
										$campaignMobileNumber->save();

										$successCount++;

									} else {
										//api returned error

										SmsGatewayErrors::create([
											'sms_id'				=> $campaignMobileNumber->id,
											'type'				=> 2, //for campaign sms
											'operator'			=> 4, // robi
											'error_code'		=> $responseData['ErrorCode'],
											'error_description'	=> $responseData['ErrorText'],
										]);

										//process refund
										//sms sending failed
										$failedCount++;

										$campaignMobileNumber->status= 3;
										$campaignMobileNumber->save();
									}
									//we get success count for the total batch
									//we get failed count for the total batch

								}
				    		} else {
				    			//response format is not ok
				    			//set batch error
								Robi::setBatchErrorStatus($campaignNumbers, 'Invalid Format');
				    		}
		        			
			         }


		        		
					} else {
						try {
							//single number processing
							$response = $client->get($gatewayinfo->operator->single_url, [
								            'query' => [
								                "Username" 		=> $gatewayinfo->username,
								                "Password" 		=> $gatewayinfo->password,
								                "From"  		=> $senderidgatewayInfo->senderid,
								                "To"   			=> $numberString,
								                "Message"  		=> $campaign->content,
								            ]
								        ]);

							
						}  catch (RequestException $e) {
							
							$failedCount += $batchCount;

							$failedStatus = 1;
						  	
						  	//gateway connection error occured
						  	//set batch error
							Robi::setBatchErrorStatus($campaignNumbers, 'Connection Error');

						}

						if($failedStatus==0){

							$use_errors = libxml_use_internal_errors(true);
							$xml_object = simplexml_load_string($response->getBody());
							if (false === $xml_object) {
								//api returned error
								$failedCount += $batchCount;

								//set batch error
								Robi::setBatchErrorStatus($campaignNumbers, 'Unexpected Response');
								$failedStatus = 1;
							}
							libxml_clear_errors();
							libxml_use_internal_errors($use_errors);

						}

			         	if($failedStatus==0){
			         		$responseData = json_decode(json_encode($xml_object),true);
				    		
				    		//single number
				    		$campaignMobileNumber = $campaignNumbers[0];

				    		//check if response format is ok
				    		if (isset($responseData['ServiceClass'])) {
				    			
				    			

								if ($responseData['ServiceClass']['ErrorCode']==0) {

									//msg sent successfully

									SmsDeliveryReports::create([
										'sms_id'			=> $campaignMobileNumber->id,
										'type'			=> 2, //for campaign sms
										'campaign_type'	=> $campaignType,
										'operator'		=> 4, // robi
										'reference'		=> $responseData['ServiceClass']['MessageId'],
										'status'			=> $responseData['ServiceClass']['Status'],
										'description'	=> $responseData['ServiceClass']['StatusText'],
										'active'			=> 1,
									]);

									$campaignMobileNumber->status= 1;
									$campaignMobileNumber->save();

									$successCount++;

								} else {
									//api returned error

									SmsGatewayErrors::create([
										'sms_id'				=> $campaignMobileNumber->id,
										'type'				=> 2, //for campaign sms
										'operator'			=> 4, // robi
										'error_code'		=> $responseData['ServiceClass']['ErrorCode'],
										'error_description'	=> $responseData['ServiceClass']['ErrorText'],
									]);

									//process refund
									//sms sending failed
									$failedCount += $batchCount;

									$campaignMobileNumber->status= 3;
									$campaignMobileNumber->save();
									
								}
							} else {
								//api returned error

								SmsGatewayErrors::create([
									'sms_id'				=> $campaignMobileNumber->id,
									'type'				=> 2, //for campaign sms
									'operator'			=> 4, // robi
									'error_code'		=> 'Unexpected',
									'error_description'	=> 'Unexpected Response',
								]);

								//process refund
								//sms sending failed
								$failedCount += $batchCount;

								$campaignMobileNumber->status= 3;
								$campaignMobileNumber->save();
							}

						}

					}

					  	
					//unset the array
					$campaignNumbers = array();
					$numberCounter = 0;
					$numberString = '';
					$batchCount = 0;

				} //batch end here
				

			} //numbers processing end

			$data['successCount'] = $successCount;
			$data['failedCount'] = $failedCount;
			return $data;

		}

		// number of this operator

		return false;
	}


	public static function setBatchErrorStatus($campaignNumbers, $errorDesc)
	{
		foreach ($campaignNumbers as $campNum) {
			//campNum is number object
			$campNum->status = 3;
			$campNum->save();

			SmsGatewayErrors::create([
				'sms_id'				=> $campNum->id,
				'type'				=> 2, //for campaign sms
				'operator'			=> 4, // robi
				'error_code'		=> 'BatchError',
				'error_description'	=> $errorDesc,
			]);

		}

		return true;
	}
	
	//To retrieve the status of a sent message
	public static function getMessageStatus($senderidgatewayInfo, $messageId)
	{

		$gatewayinfo = $senderidgatewayInfo->info;

		$client = new Client([
	        'verify' => false
        ]);

		try {

		  	$response = $client->get($gatewayinfo->operator->delivery_url, [
	            'query' => [ 
	                "Username" 		=> $gatewayinfo->username,
	                "Password" 		=> $gatewayinfo->password,
	                "MessageId"  	=> $messageId,
	            ]
	        ]);

	  	}  catch (RequestException $e) {

		  	return false;

		}

		$use_errors = libxml_use_internal_errors(true);
		$xml_object = simplexml_load_string($response->getBody());
		if (false === $xml_object) {
			//api returned error
			
			return false;
		}
		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		$responseData = json_decode(json_encode($xml_object),true);

		///start
		//check if response format is ok
		if (isset($responseData['ServiceClass'])) {
			
			if ($responseData['ServiceClass']['Status']==1) {

				//msg successfully transmitted
				return 5; // 5 means successfully transmitted (Only applicable for robi)

			} else if($responseData['ServiceClass']['Status']== -1) {
				//api returned error
				
				if($responseData['ServiceClass']['ErrorCode']== 1503) {
					/*
					message id not exist
					means either message id is too old.
					or message id is invalid
					---
					we have to skip this delivery report processing and delete the delivery report reference]
					*/
					return -1;
				
				} else {
					/*
					error occured in delivery process
					May be invalid number
					*/

					return 4; // 4 means UnDelivered
				}
			}
		}

		return false;
		
	}
	
}