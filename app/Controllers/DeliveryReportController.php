<?php 

namespace App\Controllers;

//sms system related
use App\Helper\SenderIdDetails;

//include models
use App\Models\SmsCampaigns;
use App\Models\SmsCampaignNumbersA;
use App\Models\SmsCampaignNumbersB;
use App\Models\SmsCampaignNumbersC;
use App\Models\SmsCampaignNumbersD;
use App\Models\SmsCampaignNumbersE;
use App\Models\SmsSingle;
use App\Models\SmsDeliveryReports;

//operators
use App\Helper\Operators\Airtel;
use App\Helper\Operators\Banglalink;
use App\Helper\Operators\GP;
use App\Helper\Operators\Robi;
use App\Helper\Operators\Teletalk;


class DeliveryReportController extends Controller
{
    
    public function updateDeliveryStatus($request, $response)
    {

        //process single sms delivery status
        $lastDay = date('Y-m-d',strtotime("-2 days"));
        $singlePendingNumbers = SmsSingle::whereDate('created_at', '>', $lastDay)->where('status', 1)->orderBy('id', 'desc')->limit(5000)->get();

        foreach ($singlePendingNumbers as $key => $sms) {
            //get sms delivery report reference data
            $deliveryData = SmsDeliveryReports::where('type', 1)->where('sms_id', $sms->id)->first();

            if ($deliveryData) {
                

                if ($deliveryData->operator==1) {
                    // process gp delivery report

                    $senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($sms->sender_id, $sms->operator);

                    if($sms->is_unicode==1){
                        $msgType = "3";
                    } else {
                        $msgType = "1";
                    }
                    
                    if($deliveryStatus = GP::getMessageStatus($sms->number, $senderidgatewayInfo, $msgType, $deliveryData->reference)){
                        $sms->status = $deliveryStatus;
                        $sms->save();
                        
                        //delete the delivery report reference data after successfully saving the delivery report
                        $deliveryData->delete();
                    }
                    //if deliveryStatus is false then nothing to do with it.

                } else if ($deliveryData->operator==4) {
                    // process robi delivery report
                    $senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($sms->sender_id, $sms->operator);
                    if($deliveryStatus = Robi::getMessageStatus($senderidgatewayInfo, $deliveryData->reference)){
                        if ($deliveryStatus != -1) {
                            $sms->status = $deliveryStatus;
                            $sms->save();
                        }
                        //delete the delivery report reference data after successfully saving the delivery report
                        $deliveryData->delete();
                    }
                }
            }
            //there are possibilities that some sms will not have any delivery report data. like- banglalink numbers
        }
        //single numbers processing end
        
        $campaigns = SmsCampaigns::whereDate('created_at', '>', $lastDay)->whereRaw('created_at <= DATE_SUB(NOW(), INTERVAL 3 HOUR)')->orderBy('id', 'desc')->limit(10)->get();

        
        foreach ($campaigns as $key => $campaign) {
            
            if($campaign->fetched_delivery ==0){
    
                if ($campaign->campaign_type=='A') {
                    $campaignNumbers = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->get();
                } else if($campaign->campaign_type=='B'){
                    $campaignNumbers = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->get();
                } else if($campaign->campaign_type=='C'){
                    $campaignNumbers = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->get();
                } else if($campaign->campaign_type=='D'){
                    $campaignNumbers = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->get();
                } else if($campaign->campaign_type=='E'){
                    $campaignNumbers = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->get();
                }
    
                foreach ($campaignNumbers as $campaignNumber) {
                
                    if ($campaignNumber->status!=0 && $campaignNumber->status!=1 && $campaignNumber->status!=3) {
                        //campaign not pending, not sent, not error 
                        // already retrived delivery report. SKIP THIS CAMPAIGN
                        continue 2;
                    }
    
                    //process each number
    
    
                    //get sms delivery report reference data
                    $deliveryData = SmsDeliveryReports::where('type', 2)->where('campaign_type', $campaign->campaign_type)->where('sms_id', $campaignNumber->id)->first();
                    
                    if ($deliveryData) {
                        if ($deliveryData->operator==1) {
                            // process gp delivery report
    
                            $senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($campaign->sender_id, $campaignNumber->operator);
                            if($campaign->is_unicode==1){
                                $msgType = "3";
                            } else {
                                $msgType = "1";
                            }
                            
                            if($deliveryStatus = GP::getMessageStatus($campaignNumber->number, $senderidgatewayInfo, $msgType, $deliveryData->reference)){
                                $campaignNumber->status = $deliveryStatus;
                                $campaignNumber->save();
    
                                //delete the delivery report reference data after successfully saving the delivery report
                                $deliveryData->delete();
                            }
                            //if deliveryStatus is false then nothing to do with it.
    
                        } else if ($deliveryData->operator==4) {
                            // process robi delivery report
    
                            $senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($campaign->sender_id, $campaignNumber->operator);
    
                            if($deliveryStatus = Robi::getMessageStatus($senderidgatewayInfo, $deliveryData->reference)){
    
                                if ($deliveryStatus != -1) {
                                    
                                    $campaignNumber->status = $deliveryStatus;
                                    $campaignNumber->save();
                                }
    
                                //delete the delivery report reference data after successfully saving the delivery report
                                $deliveryData->delete();
                            }
    
                        }
                        
                    }
                    //there are possibilities that some sms will not have any delivery report data. like- banglalink numbers
                    
    
                }
            
                
                $campaign->fetched_delivery = 1;
                $campaign->save();
                
            }
            
        }

        return $response->withJson([
            'status'    => "success",
            'message'   => "Request Completed",
        ], 200);

    }


    public function updateCampaignDelivery($request, $response)
    {
        $campaignId = $request->getParam('campaignId');
        $delayTime = $request->getParam('delayTime');

        sleep($delayTime);

        $campaign = SmsCampaigns::find($campaignId);
        if (!$campaign) {
            return $response->withJson([
                'status'    => "error",
                'message'   => "Invalid Request",
            ], 200);
        }

        //process campaign delivery report

        if ($campaign->campaign_type=='A') {
            $campaignNumbers = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->get();
        } else if($campaign->campaign_type=='B'){
            $campaignNumbers = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->get();
        } else if($campaign->campaign_type=='C'){
            $campaignNumbers = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->get();
        } else if($campaign->campaign_type=='D'){
            $campaignNumbers = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->get();
        } else if($campaign->campaign_type=='E'){
            $campaignNumbers = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->get();
        }
        


        foreach ($campaignNumbers as $campaignNumber) {
        
            if ($campaignNumber->status!=0 && $campaignNumber->status!=1 && $campaignNumber->status!=3) {
                //campaign not pending, not sent, not error 
                // already retrived delivery report. SKIP Number
                continue;
            }

            //process each number


            //get sms delivery report reference data
            $deliveryData = SmsDeliveryReports::where('type', 2)->where('campaign_type', $campaign->campaign_type)->where('sms_id', $campaignNumber->id)->first();
            
            if ($deliveryData) {
                

                if ($deliveryData->operator==1) {
                    // process gp delivery report

                    $senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($campaign->sender_id, $campaignNumber->operator);
                    if($campaign->is_unicode==1){
                        $msgType = "3";
                    } else {
                        $msgType = "1";
                    }
                    
                    if($deliveryStatus = GP::getMessageStatus($campaignNumber->number, $senderidgatewayInfo, $msgType, $deliveryData->reference)){
                        $campaignNumber->status = $deliveryStatus;
                        $campaignNumber->save();

                        //delete the delivery report reference data after successfully saving the delivery report
                        $deliveryData->delete();
                    }
                    //if deliveryStatus is false then nothing to do with it.

                } else if ($deliveryData->operator==4) {
                    // process robi delivery report

                    $senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($campaign->sender_id, $campaignNumber->operator);

                    if($deliveryStatus = Robi::getMessageStatus($senderidgatewayInfo, $deliveryData->reference)){

                        if ($deliveryStatus != -1) {
                            
                            $campaignNumber->status = $deliveryStatus;
                            $campaignNumber->save();
                        }

                        //delete the delivery report reference data after successfully saving the delivery report
                        $deliveryData->delete();
                    }

                }
            }
            //there are possibilities that some sms will not have any delivery report data. like- banglalink numbers
            

        }

        //delivery report fetching end

        $campaign->fetched_delivery = 1;
        $campaign->save();

        return $response->withJson([
            'status'    => "success",
            'message'   => "Request Completed",
        ], 200);
    }
    
    
    /*
    Delivery Report API
    Get delivery report of a SMS
    Get the requests from client API
    Returns the current status of the sms
    */

    public function getDeliveryReportOfSms($request, $response)
    {

        //get the requested sms id
        $requestedSms = $request->getParam('smsId');
        if (null===$requestedSms) {
            return $response->withJson([
                'status'    => "error",
                'message'   => "Invalid Request",
            ], 200);
        }

        //modified sms id based delivery report
        $sms = SmsSingle::where('sms_id', $requestedSms)->first();

        // check if smsId is invalid

        if (!$sms) {
            return $response->withJson([
                'status'    => "error",
                'message'   => "Invalid smsId",
            ], 200);
        }

        $api_token = $request->getParam('api_token');       
        // get the user
        $user = $this->auth->user($api_token);

        // check if sms is of different user
        if ($sms->user_id!=$user->id) {
            return $response->withJson([
                'status'    => "error",
                'message'   => "Invalid smsId",
            ], 200);
        }

        // all validation passed!
        // now return the sms status

        if ($sms->status==0) {
            $status = "Pending";
        } else if ($sms->status==1) {
            $status = "Sent";
        } else if ($sms->status==2 || $sms->status==5) {
            $status = "Delivered";
        } else if ($sms->status==3) {
            $status = "Failed";
        } else if ($sms->status==4) {
            $status = "UnDelivered";
        }

        return $response->withJson([
                'status'        => "success",
                'message'       => "SMS status fetched",
                'smsStatus'     => $status,
            ], 200);


        /* ----------- backup code for old delivery report system -----------
        // smsId is a combination of userId and SMS id separated by "-"
        // split the two variables

        $requestData = explode('-', $requestedSms);



        //check if requested smsId is valid
        if (isset($requestData[1])) {
            // request is ok

            // check if user is the same user

            $api_token = $request->getParam('api_token');       
            // get the user
            $user = $this->auth->user($api_token);

            if ($user->id!= $requestData[0]) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => "Invalid Request",
                ], 200);
            }

            // check if smsId is invalid
            $sms = SmsSingle::find($requestData[1]);

            if (!$sms) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => "Invalid smsId",
                ], 200);
            }

            // check if sms is of different user
            if ($sms->user_id!=$user->id) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => "Invalid smsId",
                ], 200);
            }

            // all validation passed!
            // now return the sms status

            if ($sms->status==0) {
                $status = "Pending";
            } else if ($sms->status==1) {
                $status = "Sent";
            } else if ($sms->status==2) {
                $status = "Delivered";
            } else if ($sms->status==3) {
                $status = "Failed";
            } else if ($sms->status==4) {
                $status = "UnDelivered";
            } else if ($sms->status==5) {
                $status = "Transmitted";
            }

            return $response->withJson([
                    'status'        => "success",
                    'message'       => "SMS status fetched",
                    'smsStatus'     => $status,
                ], 200);
            

        }
        */
    }
}