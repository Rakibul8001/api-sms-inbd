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

class CustomOperator
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
            $response = $client->get($gatewayinfo->operator->single_url, [
                'query' => [
                    "username"      => $gatewayinfo->username,
                    "password"      => $gatewayinfo->password,
                    "number"        => "880".$smsSingle->number,
                    "callerid"      => "info",
                    "type"          => "text",
                    "message"       => $smsSingle->content,
                ]
            ]);
        } catch (RequestException $e) {
            $smsSingle->status = 3;
            $smsSingle->save();

            SmsGatewayErrors::create([
                'sms_id'                => $smsSingle->id,
                'type'              => 1, //for campaign sms
                'operator'          => 8, // bl
                'error_code'        => 'Con Failed',
                'error_description' => 'Connection Error',
            ]);

            return 'Gateway Connection Error';
        }
        

        if ($response->getStatusCode() == 200) {
            //sms send successful

            $smsSingle->status = 1;
            $smsSingle->save();

            return 'success';
        } else {
            $smsSingle->status = 3;
            $smsSingle->save();

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
                    $response = $client->get($gatewayinfo->operator->single_url, [
                        'query' => [
                            "username"      => $gatewayinfo->username,
                            "password"      => $gatewayinfo->password,
                            "number"        => "880".$operatorNumber->number,
                            "callerid"      => "info",
                            "type"          => "text",
                            "message"       => $campaign->content,
                        ]
                    ]);
                } catch (RequestException $e) {
                    $failedCount++;

                    $operatorNumber->status = 3;
                    $operatorNumber->save();
                    SmsGatewayErrors::create([
                      'sms_id'                => $operatorNumber->id,
                      'type'              => 2, //for campaign sms
                      'operator'          => 8, // teletalk
                      'error_code'        => 'Con Failed',
                      'error_description' => 'Connection Error',
                    ]);

                    $failedStatus = 1;
                }

                if ($failedStatus==0) {
                    if ($response->getStatusCode() == 200) {
                        //sms send successful


                        $operatorNumber->status = 1;
                        $operatorNumber->save();

                        $successCount++;
                    } else {
                        $failedCount++;

                        $operatorNumber->status = 3;
                        $operatorNumber->save();
                        SmsGatewayErrors::create([
                            'sms_id'                => $operatorNumber->id,
                            'type'              => 2, //for campaign sms
                            'operator'          => 8, // teletalk
                            'error_code'        => 'Unexpected',
                            'error_description' => 'Unexpected Response',
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
