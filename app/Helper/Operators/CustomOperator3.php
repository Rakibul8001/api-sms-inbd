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

class CustomOperator3
{

    //To send SMS text messages to one recipient
    public static function sendSingleMessage($smsSingle, $senderidgatewayInfo,$clientSenderid)
    {
        dd($senderidgatewayInfo);
        $gatewayinfo = $senderidgatewayInfo->info;

        // dd($clientSenderid);

        $client = new Client([
            'verify' => false
        ]);

        try {
            
            $response = $client->post($gatewayinfo->operator->single_url, [
                'form_params' => [
                    'username'      => $gatewayinfo->username,
                    'apiId'         => $gatewayinfo->password,
                    'json'          => 'True',
                    "destination"   => "880".$smsSingle->number,
                    // "source"        => $senderidgatewayInfo->senderid,
                    "source"        => $clientSenderid,
                    "text"          => $smsSingle->content
                ]
            ]);



            // $response = $client->post($gatewayinfo->operator->cp_single_url, [
            //     'headers' => [
            //         'Content-Type' => 'application/json',
            //     ],
            //     'json' => [
			// 		"username"=> "DHITAdmin" ,
			// 		"password"=> "DHITAdmin$786" ,
			// 		"billMsisdn"=> "01708403007" ,
			// 		"usernameSecondary"=> "" ,
			// 		"passwordSecondary"=> "" ,
			// 		"billMsisdnSecondary"=> "" ,
			// 		"apiKey"=> "i1UPKvfdJxMXB7jYSNZNhsXji81P4HmW" ,
			// 		"cli"=> "01708403007" ,
			// 		"msisdnList"=> ["8801768618001"] ,
			// 		"transactionType"=> "T" ,
			// 		"messageType"=> "1" ,
			// 		"message"=> "Dear Subscriber, please recharge tk.100 to get a bonus of tk.10"
            //     ]
            // ]);


        } catch (RequestException $e) {
            $smsSingle->status = 3;
            $smsSingle->save();

            SmsGatewayErrors::create([
                'sms_id'            => $smsSingle->id,
                'type'              => 1, //for single sms
                'operator'          => 13, // fusion net
                'error_code'        => 'Con Failed',
                'error_description' => 'Connection Error',
            ]);

            return 'Gateway Connection Error';
        }


        $responseData = json_decode($response->getBody());

        if ($responseData->ErrorCode != 0) {
            $smsSingle->status = 3;
            $smsSingle->save();

            SmsGatewayErrors::create([
                'sms_id'            => $smsSingle->id,
                'type'              => 1, //for single sms
                'operator'          => 13, // fusion net
                'error_code'        => 'GatewayErr',
                'error_description' => $responseData->Description,
            ]);

            return 'Gateway Error';

        } else if ($responseData->ErrorCode == 0) {
            //sms send successful
            
            $smsSingle->status = 1;
            $smsSingle->save();
            SmsDeliveryReports::create([
					'sms_id'		=> $smsSingle->id,
					'type'			=> 1, //for single sms
					'operator'		=> 13, // metrotel
					'reference'		=> $responseData->Id,
					'status'		=> 'Sent',
					'description'	=> 'Successfully Sent',
					'active'		=> 1,
				]);

			//sms sending successful
			return 'success';
        } else {
            $smsSingle->status = 3;
            $smsSingle->save();

            SmsGatewayErrors::create([
                'sms_id'            => $smsSingle->id,
                'type'              => 1, //for single sms
                'operator'          => 13, // fusion net
                'error_code'        => 'GatewayErr',
                'error_description' => "Unexpected error",
            ]);

            return 'Gateway Error';
        }

        //end

        return false;
    }

    //To send same SMS to multiple recipients
    public static function sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus = 0)
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

            if ($campaign->is_unicode==1) {
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
                            'username'      => $gatewayinfo->username,
                            'apiId'         => $gatewayinfo->password,
                            'json'          => 'True',
                            "destination"   => "880".$operatorNumber->number,
                            "source"        => $senderidgatewayInfo->senderid,
                            "text"          => $campaign->content,
                        ]
                    ]);
                } catch (RequestException $e) {
                    $failedCount++;

                    $operatorNumber->status = 3;
                    $operatorNumber->save();
                    SmsGatewayErrors::create([
                      'sms_id'                => $operatorNumber->id,
                      'type'              => 2, //for campaign sms
                      'operator'          => 13, // teletalk
                      'error_code'        => 'Con Failed',
                      'error_description' => 'Connection Error',
                    ]);

                    $failedStatus = 1;
                }

                if ($failedStatus==0) {
                    //
                    $responseData = json_decode($response->getBody());

                    if ($responseData->ErrorCode != 0) {
                        $failedCount++;

                        $operatorNumber->status = 3;
                        $operatorNumber->save();

                        SmsGatewayErrors::create([
                            'sms_id'            => $operatorNumber->id,
                            'type'              => 2, //for single sms
                            'operator'          => 13, // fusion net
                            'error_code'        => 'GatewayErr',
                            'error_description' => $responseData->Description,
                        ]);

                        $failedStatus = 1;

                    } else if ($responseData->ErrorCode == 0) {
                        //sms send successful
                        
                        $operatorNumber->status = 1;
                        $operatorNumber->save();

                        $successCount++;

                        SmsDeliveryReports::create([
                                'sms_id'		=> $operatorNumber->id,
                                'type'			=> 2, //for single sms
                                'operator'		=> 13, // fusion
                                'reference'		=> $responseData->Id,
                                'status'		=> 'Sent',
                                'description'	=> 'Successfully Sent',
                                'active'		=> 1,
                            ]);
                    } else {
                        $failedCount++;

                        $operatorNumber->status = 3;
                        $operatorNumber->save();
                        SmsGatewayErrors::create([
                            'sms_id'                => $operatorNumber->id,
                            'type'              => 2, //for campaign sms
                            'operator'          => 13, // teletalk
                            'error_code'        => 'GatewayErr',
                            'error_description' => "Unexpected error",
                        ]);

                        $failedStatus = 1;
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
