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

class CustomOperator2
{

    //To send SMS text messages to one recipient
    public static function sendSingleMessage($smsSingle, $senderidgatewayInfo,$clientSenderid)
    {

        $gatewayinfo = $senderidgatewayInfo->info;

        // dd($clientSenderid);

        $client = new Client([
            'verify' => false
        ]);

        try {
            
            $response = $client->get($gatewayinfo->operator->single_url, [
                'query' => [
                    'api_key'       => "R2000184626aae13cb19e4.69465805",
                    'type'          => "text",
                    "contacts"      => "0".$smsSingle->number,
                    "senderid"      => $senderidgatewayInfo->senderid,
                    "msg"           => $smsSingle->content
                ]
            ]);
        } catch (RequestException $e) {
            $smsSingle->status = 3;
            $smsSingle->save();

            SmsGatewayErrors::create([
                'sms_id'            => $smsSingle->id,
                'type'              => 1, //for campaign sms
                'operator'          => 12, // bl
                'error_code'        => 'Con Failed',
                'error_description' => 'Connection Error',
            ]);

            return 'Gateway Connection Error';
        }
        

        if (substr($response->getBody(), 0, 2 ) === "10") {
            $smsSingle->status = 3;
            $smsSingle->save();

            return 'Gateway Error';

        } else {
            //sms send successful
            $reference = str_replace("SMS SUBMITTED: ID - ","",$response->getBody());
            
            //SMS SUBMITTED: ID - 
            $smsSingle->status = 1;
            $smsSingle->save();
            SmsDeliveryReports::create([
					'sms_id'		=> $smsSingle->id,
					'type'			=> 1, //for single sms
					'operator'		=> 12, // metrotel
					'reference'		=> $reference,
					'status'		=> 'Sent',
					'description'	=> 'Successfully Sent',
					'active'		=> 1,
				]);

			//sms sending successful
			return 'success';
        }

        //end

        return false;
    }

    //To send same SMS to multiple recipients
 
    public static function sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus = 0)
    {
        
//         $data['successCount'] = '1111';
// 			$data['failedCount'] = '2222';
// 			return $data;
        
        $batchLimit = 100;
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
				    
				    //////////////////
				    
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
					
				    //send sms one by one
                    $client = new Client([
                        'verify' => false
                    ]);
    
                    $failedStatus = 0;
                    
                        
                    try {
                        $response = $client->get($gatewayinfo->operator->single_url, [
                                'query' => [
                                    'api_key'       => "R2000184626aae13cb19e4.69465805",
                                    'type'          => "text",
                                    "contacts"      => $numberString,
                                    "senderid"      => $senderidgatewayInfo->senderid,
                                    "msg"           => $campaign->content
                                ]
                            ]);
                    } catch (RequestException $e) {
                        $failedCount += $batchCount;

						$failedStatus = 1;
						  	
						//gateway connection error occured
						//set batch error
						CustomOperator2::setBatchErrorStatus($campaignNumbers, 'Connection Error');
                    }
    
                    if ($failedStatus==0) {
                        if (substr($response->getBody(), 0, 2 ) === "10") {
                            
                            $failedCount += $batchCount;

							$failedStatus = 1;
                            CustomOperator2::setBatchErrorStatus($campaignNumbers, 'Gateway Error');
                
                        } else {
                            //sms send successful
                            $successCount +=  $batchCount;
                            $reference = str_replace("SMS SUBMITTED: ID - ","",$response->getBody());
                            
                            CustomOperator2::setSmsSuccess($campaignNumbers, $reference);
                            
                            
                            //we get success count for the total batch
							//we get failed count for the total batch
							
                        }
                    }
				    /////////////////
				    
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

	public static function setSmsSuccess($campaignNumbers, $reference)
	{
	    foreach ($campaignNumbers as $campNum) {
			//campNum is number object
			$campNum->status = 1;
			$campNum->save();

            SmsDeliveryReports::create([
					'sms_id'		=> $campNum->id,
					'type'			=> 2, //for campaign sms
					'operator'		=> 12, // metrotel
					'reference'		=> $reference,
					'status'		=> 'Sent',
					'description'	=> 'Successfully Sent',
					'active'		=> 1,
				]);

		}

		return true;
	}
	
	public static function setBatchErrorStatus($campaignNumbers, $errorDesc)
	{
		foreach ($campaignNumbers as $campNum) {
			//campNum is number object
			$campNum->status = 3;
			$campNum->save();

			SmsGatewayErrors::create([
				'sms_id'			=> $campNum->id,
				'type'				=> 2, //for campaign sms
				'operator'			=> 12, // robi
				'error_code'		=> 'BatchError',
				'error_description'	=> $errorDesc,
			]);

		}

		return true;
	}
                


    //To retrieve the status of a sent message
    public static function getMessageStatus()
    {

        return true;
    }
}
