<?php

namespace App\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Str;
use Illuminate\Database\Capsule\Manager as DB;


//sms system related
use App\Helper\UserBalanceManager;
use App\Helper\SenderIdDetails;
use App\Helper\SmsSystem as SmsSystm;
use App\Helper\ContactsAndGroups;

//include models
use App\Models\Users;
use App\Models\SmsCampaigns;
use App\Models\SmsCampaignNumbersA;
use App\Models\SmsCampaignNumbersB;
use App\Models\SmsCampaignNumbersC;
use App\Models\SmsCampaignNumbersD;
use App\Models\SmsCampaignNumbersE;
use App\Models\SmsDeliveryReports;
use App\Models\SmsSingle;
use App\Models\SmsSender;
use App\Models\SmsSenderTtk;
use App\Models\AccountTransaction;
use App\Models\Contact;
use App\Models\SmsLowcostCampaigns;
use App\Models\SmsLowcost;


use App\Models\Operators;

use App\Core\ContactsAndGroups\ContactsAndGroupsDetails;

//operators
use App\Helper\Operators\Airtel;
use App\Helper\Operators\Banglalink;
use App\Helper\Operators\GP;
use App\Helper\Operators\Robi;
use App\Helper\Operators\Teletalk;
use App\Helper\Operators\CustomOperator;
use App\Helper\Operators\CustomOperator2;
use App\Helper\Operators\CustomOperator3;
use App\Models\Divider;
use App\Models\SenderidMaster;
use App\Models\SenderidUsers;
use App\Models\SenderidGateways;

class SmsApiController extends Controller
{



    /**
     * Contact group service
     *
     * @var App\Core\ContactsAndGroups\ContactsAndGroupsDetails
     */
    protected $contactgroup;


    /**
     * sms message count
     *
     * @var string
     */
    protected $messagecount;

    /**
     * sms message type
     *
     * @var string
     */
    protected $messagetype;

    /**
     * Total contacts in groups
     *
     * @var integer
     */
    protected $totalValidContact = 0;

    protected $template;

    /**
     * SmsSend service
     *
     * @var App\Core\SmsSend\SmsSendDetails
     */
    protected $smsSystem;



    function convertNumberToAlphanumeric($number)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($characters);
        $result = '';

        while ($number > 0) {
            $index = ($number - 1) % $base;
            $result = $characters[$index] . $result;
            $number = floor(($number - 1) / $base);
        }

        return $result;
    }



    public function sendSMS($request, $response)
    {
        
        error_reporting(0);

        // if (!$request->getParam('message') ||!$request->getParam('senderid')||!$request->getParam('api_token')) 
        if (!$request->getParam('message') ||!$request->getParam('api_token')) 
        {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Required field is empty, message content & senderid are required parameter',
            ], 200);
        }

        $api_token = $request->getParam('api_token');

        $sentThrough = 1;
        if ($request->getParam('source')) {
            $sentThrough = 0;
        }
        
        
        //get the user
        $user = $this->auth->user($api_token);

        if ($user->status == 'n') {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'We have found unethical transection from your account, your account is blocked until the issue is solve. Please contact support team.',
            ], 200);
        }

        $userId = $user->id;

        //new modified Rakibul

        $clientSenderids = SenderidUsers::where('user', $userId)->get();
        // dd($clientSenderids);

        $clientSenderidsArr=[];
        foreach ($clientSenderids as $senderId){
            $clientSenderidsArr[]= $senderId->getSenderid->name;
        }

        // $clientSenderidsArr = $request->getParam('senderidsArr');
        $clientSenderidsArr = htmlspecialchars(implode(',', $clientSenderidsArr));
        $clientSenderidsArr = explode(',',$clientSenderidsArr);
        // dd($clientSenderidsArr);

        // $clientSenderidsArr=["BoiBitan","SMSinBD", "DataHost IT"];

        $arrayLength = count($clientSenderidsArr);

        $randomNumber = mt_rand(0, $arrayLength);
        $clientSenderid =  $clientSenderidsArr[$randomNumber];

        // dd($clientSenderid);


        $message = $request->getParam('message');
        $schedule = $request->getParam('schedule');
        $scheduledTime = $request->getParam('target_time');

        $isScheduled = 0;
        if ($schedule && $schedule==1) {
            $isScheduled = 1;
        }

        $senderIdName = $request->getParam('senderid');

        //get master senderId
        $senderId = SenderIdDetails::getSenderIdByNameIfValid($userId, $senderIdName);

        if (!$senderId) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Invalid Sender ID.',
            ], 200);
        }
        



        $contacts = [];


        //the condition block is used for debugging codes without affecting the system
        if($request->getParam('debug') && $request->getParam('debug')==1){

            // var_dump(Str::length($request->getparam('message'))); //strlen($message);

            // if (strlen($request->getparam('message')) != strlen(utf8_decode($request->getparam('message'))))
            // {
            //     echo 'unicode';
            // } else {
            //     echo 'text';                
            // }

            // $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

            // if (!$this->messagecount) {
            //     return $response->withJson([
            //         'status'    => "error",
            //         'message'   => 'SMS length exceeded max limit of 8 sms.',
            //     ], 200);
            // }
        }

        $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

        if (!$this->messagecount) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'SMS length exceeded max limit of 8 sms.',
            ], 200);
        }

        // OTP restriction
        if($user->otp_allowed!=1){

            $messageContent = $request->getparam('message');

            $numberMap = [
                '০' => 0,
                '১' => 1,
                '২' => 2,
                '৩' => 3,
                '৪' => 4,
                '৫' => 5,
                '৬' => 6,
                '৭' => 7,
                '৮' => 8,
                '৯' => 9,
            ];

            $str = str_replace(' ', '', $messageContent);
            $str= str_replace(array_keys($numberMap), $numberMap, $str);
            
            if($sentThrough==0){
                $str = str_ireplace( array('.','=','~','@','#','%','$','^','&','*',',','_','+', '\'', '"', ',' , ';', '<', '>' ), '', $str);
            } else {
                $str = str_ireplace( array('.','/','-','=','~','@','#','%','$','^','&','*',',','_','+', '\'', '"', ',' , ';', '<', '>' ), '', $str);
            }

            $years = array(2022,2023);
            preg_match_all('!\d+!', $str, $matches);
            foreach($matches[0] as $numericText){
                if(strlen($numericText)>=4 and strlen($numericText)<=8){
                    if(!in_array($numericText, $years)){
                        return $response->withJson([
                            'status'    => "error",
                            'message'   => 'Your sms contains number(s) like OTP and you are not allowed to send OTP. Please change the sms content or contact support.',
                        ], 200);
                        exit;
                    }
                }
            }
        }
        

        $this->messagetype = SmsSystm::getMessageType($request->getparam('message'));

        if ($this->messagetype=='unicode') {
                $is_unicode = 1;
        } else {
            $is_unicode = 0;
        }

        

        

        $currentBalance = 0;
        $totalmaskbal = $user->mask_balance;
        $totalnonmaskbal = $user->nonmask_balance;
        $totalvoicebal = $user->voice_balance;

        $root_userid = $user->root_user_id;

        $reseller_id = $user->reseller_id;

        $category = $senderId->type;



        if ($senderId->type==2) {
            $senderidType = 'nomask';
            $currentBalance = $user->nonmask_balance;
        } else if ($senderId->type==1) {
            $senderidType = 'mask';
            $currentBalance = $user->mask_balance;
        }

        $totalSms = 0;

        if ($request->getParam('numbertype')) {
            $numberType = $request->getParam('numbertype');
        } else {
            $numberType = 'single';
        }

      

        //Single contact number start
        if ($numberType == 'single') {
            if (!$request->getParam('contact_number')) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'contact_number is required parameter',
                ], 200);
            }

            $contactlist = is_array($request->getParam('contact_number')) ? $request->contact_number : explode(",", str_replace("\n", ",", str_replace(" ", ",", $request->getParam('contact_number'))));

            //get valid contacts from this array of numbers
            $contacts = array();
            foreach ($contactlist as $mobileNo) {
                if ($mobileNo = ContactsAndGroups::mobileNumber($mobileNo)) {
                    array_push($contacts, $mobileNo);
                }
            }

     

            if (isset($contacts[0])) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
         
            } else {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Invalid format of contact numbers. Please check your cotact numbers',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);
  
            $totalSms = $this->messagecount*$this->totalValidContact;

            if ($totalmaskbal < $totalSms && $senderidType == 'mask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient mask sms balance',
                ], 200);
            }

            if ($totalnonmaskbal < $totalSms && $senderidType == 'nomask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient nonmask sms balance',
                ], 200);
            }

            if ($totalvoicebal < $totalSms && $senderidType == 'voice') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient voice sms balance',
                ], 200);
            }
        } else {
            $contactGroup = explode(',', $request->getParam('contactgroup'));
            
            $contacts = $numberType == 'contgroup' ? $this->getValidNumbers($userId, $contactGroup) : $this->validMobileFromFile($userId, $request);

            if (isset($contacts)) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
                // dd($contacts);
            }

            if (!$contacts) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'There is an error, problem may be invalid file format!',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            // dd($this->totalValidContact);

            $totalSms = ($this->totalValidContact*$this->messagecount);

            if ($totalmaskbal < $totalSms && $senderidType == 'mask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient mask sms balance',
                ], 200);
            }

            if ($totalnonmaskbal < $totalSms && $senderidType == 'nomask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient nonmask sms balance',
                ], 200);
            }

            if ($totalvoicebal < $totalSms && $senderidType == 'voice') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient voice sms balance',
                ], 200);
            }
        }

        $newBalance = $currentBalance - $totalSms;

        //deduce the user's balance
        UserBalanceManager::updateBalance($user->id, $category, $newBalance);


        // print_r($contacts); die();

        //here is the array of numbers in integer formatted

        /*
        * if total numbers are within 5 and no campaign request then we will take the requested numbers as individual numbers
        * but if the total numbers exceeds 5 and there is no campaign request then we will convert it to a campaign
        * if there is any campaign request then we will take the equested numbers as campaign
        */
        if ($this->totalValidContact>1) {
            /*
            * request is a campaign.
            * process it as a campaign
            *
            * ----------Parameters------
            * campaign_name
            * campaign_description
            * scheduled
            * target_time
            *
            */
            
            $campaignId = date('y') .$user->id. date('mdhis'). mt_rand(100, 999);

            $campaignName = "";
            if ($request->getParam('campaign_name')) {
                $campaignName = $request->getParam('campaign_name');
            } else {
                $campaignName = "Campaign ". $campaignId;
            }

            $campaignStatus = 'Pending';
            if ($isScheduled==1) {
                $campaignStatus = 'Scheduled';
            }

            $numbersCount = count($contacts);
            if ($numbersCount<50) {
                $campaignType = "A";
            } else if ($numbersCount<500) {
                $campaignType = "B";
            } else if ($numbersCount<3000) {
                $campaignType = "C";
            } else if ($numbersCount<5000) {
                $campaignType = "D";
            } else {
                $campaignType = "E";
            }

            $campaign = SmsCampaigns::create([
                'campaign_type'         => $campaignType,
                'campaign_no'           => $campaignId,
                'campaign_name'         => $campaignName,
                'campaign_description'  => $request->getParam('campaign_description'),
                'user_id'               => $user->id,
                'sender_id'             => $senderId->id,
                'is_unicode'            => $is_unicode,
                'category'              => $category,
                'content'               => $message,
                'sms_qty'               => $this->messagecount,
                'total_numbers'         => $this->totalValidContact,
                'sent_through'          => $sentThrough,//web
                'is_scheduled'          => $isScheduled,
                'scheduled_time'        => $scheduledTime,
                'status'                => $campaignStatus,
                'active'                => 1,

            ]);

            //create the account transaction
            $transaction = AccountTransaction::create([
                'type'        => $category,
                'user'        => $user->id,
                'txn_type'    => $senderidType.'_campaign',
                'reference'   => $campaign->id,
                'debit'       => $totalSms,
                'credit'      => 0,
                'balance'     => $newBalance,
                'note'        => 'Campaign created',
                'active'      => 1,
            ]);



            //check if MNP Dipping is enabled
            if ($user->live_dipping == true) {
                //process each number's operator checking thow MNP Dipping API
                $wrongNumberCount = 0;
                $wrongNumberKeys = array();
                
                //
                //checking campaign type outside the loop increases performance
                //
                
                if ($campaignType=='A') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersA::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='B') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersB::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='C') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersC::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='D') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersD::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='E') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersE::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                }



                if ($wrongNumberCount>0) {
                    //credit the wrong numbers smscount
                    $creditWrongNumbers = $wrongNumberCount * $this->messagecount;
                    $newBalance = $newBalance +$creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);
                    
                    //create the account transaction
                    $transaction = AccountTransaction::create([
                        'type'        => $category,
                        'user'        => $user->id,
                        'txn_type'    => $senderidType.'_campaign',
                        'reference'   => $campaign->id,
                        'debit'       => 0,
                        'credit'      => $creditWrongNumbers,
                        'balance'     => $newBalance,
                        'note'        => $wrongNumberCount. ' wrong numbers in campaign',
                        'active'      => 1,
                    ]);

                    //remove the numbers from contacts
                    foreach ($wrongNumberKeys as $itemKey) {
                        unset($contacts[$itemKey]);
                        // $contacts = array_values($contacts); //if need to reindex
                    }
                }
                //end of wrong numbers processing

            //mnp dipping ends
            } else {
                //if mnp dipping is disabled

                //
                //checking campaign type outside the loop increases performance
                //
   
                if ($campaignType=='A') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersA::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='B') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersB::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='C') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersC::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='D') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersD::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='E') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersE::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                }
                //insertion complete

                //set operator by prefix

                if ($campaignType=='A') {
                    $campaignNumbersTable = 'sms_campaign_numbers';
                } else if ($campaignType=='B') {
                    $campaignNumbersTable = 'sms_campaign_numbersB';
                } else if ($campaignType=='C') {
                    $campaignNumbersTable = 'sms_campaign_numbersC';
                } else if ($campaignType=='D') {
                    $campaignNumbersTable = 'sms_campaign_numbersD';
                } else if ($campaignType=='E') {
                    $campaignNumbersTable = 'sms_campaign_numbersE';
                }

                $db_conx = $this->DbConnect->connect();
                //gp
                $sql = "UPDATE $campaignNumbersTable SET `operator`=1 WHERE campaign_id=$campaign->id AND number LIKE '17%' OR number LIKE '13%'";
                $query = mysqli_query($db_conx, $sql);

                //bl
                $sql = "UPDATE $campaignNumbersTable SET `operator`=2 WHERE campaign_id=$campaign->id AND number LIKE '19%' OR number LIKE '14%'";
                $query = mysqli_query($db_conx, $sql);

                //airtel
                $sql = "UPDATE $campaignNumbersTable SET `operator`=3 WHERE campaign_id=$campaign->id AND number LIKE '16%'";
                $query = mysqli_query($db_conx, $sql);

                //robi
                $sql = "UPDATE $campaignNumbersTable SET `operator`=4 WHERE campaign_id=$campaign->id AND number LIKE '18%'";
                $query = mysqli_query($db_conx, $sql);

                //telealk
                $sql = "UPDATE $campaignNumbersTable SET `operator`=5 WHERE campaign_id=$campaign->id AND number LIKE '15%'";
                $query = mysqli_query($db_conx, $sql);
                //operator processing complete

            //non dipping end
            }

            if ($request->getParam('process_type')) {
                if ($request->getParam('process_type')=='async') {
                    // return the campaign id and asynchronously process the operator processing
                    // dd($this->baseUrl);
                    $operatorNumbers = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('operator', 1)->get();
                    // dd($operatorNumbers);
                    $stringNumber = "";
                    $numberPrefix = "0";
                    foreach($operatorNumbers as $operator){
                        $stringNumber = "88".$numberPrefix.$operator->number;
                        // dd($operator->number);
                    }
                    $this->fastRequest($this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token'));
                    // dd($operatorNumbers[0]);
                    
                    return $response->withJson([
                        'status'    => "campaign_started",
                        'message'   => "Campaign sms processing started...",
                        'url'   => $this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token'),
                        'campaign'  => $campaign->id
                    ], 200);
                    // dd("faster response");
                    
                }
            }

            // process operator wise processing
            $totalSuccessCount = 0;
            $totalFailedCount = 0;

            //start operator processing of campaign

            $outputUrls = [];
            $senderidGateways = [];

            $numberStatus = 0; //pending numbers initially

            for ($i=1; $i <=5; $i++) {
                $inputOperator = $i;

                if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $inputOperator)) {
                    if ($senderidgatewayInfo->output_operator ==1) {
                        // Grameenphon

                        $outputUrls[$inputOperator] = "/internal/process-gp";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==2) {
                        // Banglalink

                        $outputUrls[$inputOperator] = "/internal/process-bl";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==4) {
                        //there is no gateway for Airtel. gateway is robi. so output operator can not be 3

                        // Airtel/Robi

                        $outputUrls[$inputOperator] = "/internal/process-robi";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==5) {
                        // TeleTalk

                        $outputUrls[$inputOperator] = "/internal/process-ttk";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==12) {
                        // custom2

                        // dd("Custom");
                        $outputUrls[$inputOperator] = "/internal/process-custom2";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    }  else if ($senderidgatewayInfo->output_operator ==13) {
                        // custom3

                        $outputUrls[$inputOperator] = "/internal/process-custom3";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    }
                }
            }


            $client = new Client(['base_uri' => $this->baseUrl]);

            // Initiate each request but do not block
            $promises = [
                'gp'    => $client->getAsync($outputUrls[1], [
                    'query' => [
                            "api_token"         => $this->apiToken,
                            "campaign"          => $campaign->id,
                            "numberStatus"      => $numberStatus,
                            "senderidGateway"   => $senderidGateways[1]
                        ]
                    ]),

                'bl'   => $client->getAsync($outputUrls[2], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[2]
                        ]
                    ]),

                'airtel'   => $client->getAsync($outputUrls[3], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[3]
                        ]
                    ]),
                'robi'   => $client->getAsync($outputUrls[4], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[4]
                        ]
                    ]),

                'ttk'   => $client->getAsync($outputUrls[5], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[5]
                        ]
                    ]),
                'custom'   => $client->getAsync($outputUrls[11], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[11]
                            ]
                        ]),
                'metrotel'   => $client->getAsync($outputUrls[12], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaignId,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[12]
                        ]
                    ]),
                'fusion'   => $client->getAsync($outputUrls[13], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaignId,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[13]
                        ]
                    ]),
            ];

            // Wait for the requests to complete, even if some of them fail
            $responses = Promise\Utils::settle($promises)->wait();

            $gpResponse      = json_decode($responses['gp']['value']->getBody());
            $blResponse      = json_decode($responses['bl']['value']->getBody());
            $airtelResponse  = json_decode($responses['airtel']['value']->getBody());
            $robiResponse    = json_decode($responses['robi']['value']->getBody());
            $ttkResponse     = json_decode($responses['ttk']['value']->getBody());
            $customResponse  = json_decode($responses['custom']['value']->getBody());
            $custom2Response  = json_decode($responses['metrotel']['value']->getBody());
            $custom3Response  = json_decode($responses['fusion']['value']->getBody());


            if ($gpResponse!="failed") {
                $totalSuccessCount += $gpResponse->successCount;
                $totalFailedCount  += $gpResponse->failedCount;
            }
            if ($blResponse!="failed") {
                $totalSuccessCount += $blResponse->successCount;
                $totalFailedCount  += $blResponse->failedCount;
            }
            if ($airtelResponse!="failed") {
                $totalSuccessCount += $airtelResponse->successCount;
                $totalFailedCount  += $airtelResponse->failedCount;
            }
            if ($robiResponse!="failed") {
                $totalSuccessCount += $robiResponse->successCount;
                $totalFailedCount  += $robiResponse->failedCount;
            }
            if ($ttkResponse!="failed") {
                $totalSuccessCount += $ttkResponse->successCount;
                $totalFailedCount  += $ttkResponse->failedCount;
            }
            if ($customResponse!="failed") {
                $totalSuccessCount += $customResponse->successCount;
                $totalFailedCount  += $customResponse->failedCount;
            }
            if ($custom2Response!="failed") {
                $totalSuccessCount += $custom2Response->successCount;
                $totalFailedCount  += $custom2Response->failedCount;
            }
            if ($custom3Response!="failed") {
                $totalSuccessCount += $custom3Response->successCount;
                $totalFailedCount  += $custom3Response->failedCount;
            }

            //end operator processing of campaign

            $campaign->status = 'Complete';
            $campaign->save();

            $delayTime = 300;


            //process refund here
            //get failed numbers in the campaign
            if ($campaignType=='A') {
                $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 300;
            } else if ($campaignType=='B') {
                $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 900;
            } else if ($campaignType=='C') {
                $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 2400;
            } else if ($campaignType=='D') {
                $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 3600;
            } else if ($campaignType=='E') {
                $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 7200;
            }





            if ($failedSmsCount>0) {
                //campaign has failed number

                $user = Users::find($campaign->user_id);
                if ($senderId->type==2) {
                    $currentBalance = $user->nonmask_balance;
                } else if ($senderId->type==1) {
                    $currentBalance = $user->mask_balance;
                }
                $creditFailedCount = $failedSmsCount * $campaign->sms_qty;




                
                //no chance of having any old refund for the campaign because this is the sms sending api request
                
                $newBalance = $currentBalance + $creditFailedCount;

                //update the user's balance
                UserBalanceManager::updateBalance($user->id, $category, $newBalance);


                //create the account transaction
                $transaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_cam_refund',
                    'reference'   => $campaign->id,
                    'debit'       => 0,
                    'credit'      => $creditFailedCount,
                    'balance'     => $newBalance,
                    'note'        => $failedSmsCount. ' numbers failed in campaign',
                    'active'      => 1,
                ]);

                //new entry created
                //refund done

                //update failed sms count in campaign table
                $campaign->failed_sms = $creditFailedCount;
                $campaign->save();
            }

            // asynchronous call for fetching delivery request
            $this->fastRequest($this->baseUrl.'internal/campaign-dl-report?api_token='.$this->apiToken.'&campaignId='.$campaign->id.'&delayTime='.$delayTime);

            return $response->withJson([
                'status'    => "success",
                'message'   => "Campaign sms sent successfully",
                'success'   => $totalSuccessCount,
                'failed'    => $totalFailedCount,
            ], 200);
        } else {
            //not a campaign or single number request

            $mobile = $contacts[0];

            $msgStatus = 'Pending';
            if ($isScheduled==1) {
                $msgStatus = 'Scheduled';
            }

            // check if already a transaction is created or not
            $accTransaction = AccountTransaction::where('user', $user->id)->where('type', $category)->where('txn_type', $senderidType.'_single')->where('credit', 0)->whereDate('created_at', '=', date('Y-m-d'))->first();

            if ($accTransaction) {
                // increase the transaction units amount by number of sms
                $accTransaction->debit = $accTransaction->debit+$totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();
            } else {
                 //no transaction of the user for the day

                //create the account transaction
                $accTransaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_single',
                    'debit'       => $totalSms,
                    'credit'      => 0,
                    'balance'     => $newBalance,
                    'note'        => 'Individual sms sent through web', //web or api
                    'active'      => 1,
                ]);
            }

            //generate message Id
            $lastMsgId = null;

            $lastMsg = SmsSingle::orderBy('id', 'desc')->first();
            if ($lastMsg) {
                $lastMsgId = $lastMsg->id;
                $lastMsgId++;
            } else {
                $lastMsgId = 1;
            }
            $smsId = date('ymd') .$user->id. $lastMsgId. mt_rand(10000, 99099);

            $operatorId = null;

            //check if MNP Dipping is enabled
            if ($user->live_dipping == true) {
                //process number's operator checking thow MNP Dipping API

                // check each numbers through MNP dipping
                    
                if ($optId = SmsSystm::checkMnpOperator($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }


            //mnp dipping ends
            } else {
                //if mnp dipping is disabled

                // check operator by prefix
                if ($optId = SmsSystm::getOperatorByPrefix($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    
                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }
                

            //non dipping end
            }

            
            // insert the number to individual-numbers
            $smsSingle = SmsSingle::create([
                'sms_id'            => $smsId,
                'user_id'           => $user->id,
                'sender_id'         => $senderId->id,
                'category'          => $category,
                'number'            => $mobile,
                'operator'          => $operatorId,
                'is_unicode'        => $is_unicode,
                'content'           => $message,
                'qty'               => $this->messagecount,
                'sent_through'      => $sentThrough, //0=web, 1=api
                'is_scheduled'      => $isScheduled,
                'scheduled_time'    => $scheduledTime,
                'status'            => 0,
                'active'            => 1,
            ]);
            // insertion complete


            // process operator wise processing
            if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $operatorId)) {
                if ($senderidgatewayInfo->output_operator ==1) {
                    // Grameenphon
                    $operatorResponse = GP::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==2) {
                    // Banglalink
                    $operatorResponse = Banglalink::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==3 || $senderidgatewayInfo->output_operator ==4) {
                    // Airtel/Robi
                    $operatorResponse = Robi::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==5) {
                    // TeleTalk
                    $operatorResponse = Teletalk::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 11) {
                    // custom operator
                    $operatorResponse = CustomOperator::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 12) {
                    // custom operator 2
                    $operatorResponse = CustomOperator2::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 13) {
                    // custom operator 3
                    $operatorResponse = CustomOperator3::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                }

            }

            if ($operatorResponse  == 'success') {
                return $response->withJson([
                    'status'    => "success",
                    'message'   => "SMS sent successfully",
                    'smsid'     => $smsId,
                    'SmsCount'  => $smsSingle->qty,
                    // 'gateway' => $senderidgatewayInfo,
                    // 'apiResponse' => $operatorResponse
                ], 200);

            } else {

                $newBalance = $currentBalance + $totalSms;

                //deduce the user's balance
                UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                //refund to transaction
                $accTransaction->debit = $accTransaction->debit + $totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();

                //refund done

                
                return $response->withJson([
                        'status'    => "error",
                        'message'   => $operatorResponse,
                    ], 200);
            }

            //System Error
            //process refund here
            $newBalance = $currentBalance + $totalSms;

            //deduce the user's balance
            UserBalanceManager::updateBalance($user->id, $category, $newBalance);

            //refund to transaction
            $accTransaction->debit = $accTransaction->debit + $totalSms;
            $accTransaction->balance = $accTransaction->balance + $totalSms;
            $accTransaction->save();

            //refund done


            return $response->withJson([
                'status'    => "error",
                'message'   => 'System Error',
            ], 200);
        }
        //end of individual

        return $response->withJson([
            'status'    => "error",
            'message'   => 'System Error',
        ], 200);
    }

    //weblink api
    public function sendSMSweblink($request, $response)
    {
        
        error_reporting(0);


        // if (!$request->getParam('message') ||!$request->getParam('senderid')||!$request->getParam('api_token')) 
        if (!$request->getParam('message') ||!$request->getParam('api_token')) 
        {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Required field is empty, message content & senderid are required parameter',
            ], 200);
        }

        $api_token = $request->getParam('api_token');

        $sentThrough = 1;
        if ($request->getParam('source')) {
            $sentThrough = 0;
        }
        
        
        //get the user
        $user = $this->auth->user($api_token);

        if ($user->status == 'n') {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'We have found unethical transection from your account, your account is blocked until the issue is solve. Please contact support team.',
            ], 200);
        }

        $userId = $user->id;

        //new modified Rakibul

        $clientSenderids = SenderidUsers::where('user', $userId)->get();
        // dd($clientSenderids);

        $clientSenderidsArr=[];
        foreach ($clientSenderids as $senderId){
            $clientSenderidsArr[]= $senderId->getSenderid->name;
        }

        // $clientSenderidsArr = $request->getParam('senderidsArr');
        $clientSenderidsArr = htmlspecialchars(implode(',', $clientSenderidsArr));
        $clientSenderidsArr = explode(',',$clientSenderidsArr);
        // dd($clientSenderidsArr);

        // $clientSenderidsArr=["BoiBitan","SMSinBD", "DataHost IT"];

        $arrayLength = count($clientSenderidsArr);

        $randomNumber = mt_rand(0, $arrayLength);
        $clientSenderid =  $clientSenderidsArr[$randomNumber];

        // dd($clientSenderid);


        $message = $request->getParam('message');
        $schedule = $request->getParam('schedule');
        $scheduledTime = $request->getParam('target_time');

        $isScheduled = 0;
        if ($schedule && $schedule==1) {
            $isScheduled = 1;
        }

        $senderIdName = $request->getParam('senderid');

        //get master senderId
        $senderId = SenderIdDetails::getSenderIdByNameIfValid($userId, $senderIdName);

        if (!$senderId) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Invalid Sender ID.',
            ], 200);
        }
        



        $contacts = [];


        //the condition block is used for debugging codes without affecting the system
        if($request->getParam('debug') && $request->getParam('debug')==1){

            // var_dump(Str::length($request->getparam('message'))); //strlen($message);

            // if (strlen($request->getparam('message')) != strlen(utf8_decode($request->getparam('message'))))
            // {
            //     echo 'unicode';
            // } else {
            //     echo 'text';                
            // }

            // $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

            // if (!$this->messagecount) {
            //     return $response->withJson([
            //         'status'    => "error",
            //         'message'   => 'SMS length exceeded max limit of 8 sms.',
            //     ], 200);
            // }
        }

        $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

        if (!$this->messagecount) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'SMS length exceeded max limit of 8 sms.',
            ], 200);
        }

        // OTP restriction
        if($user->otp_allowed!=1){

            $messageContent = $request->getparam('message');

            $numberMap = [
                '০' => 0,
                '১' => 1,
                '২' => 2,
                '৩' => 3,
                '৪' => 4,
                '৫' => 5,
                '৬' => 6,
                '৭' => 7,
                '৮' => 8,
                '৯' => 9,
            ];

            $str = str_replace(' ', '', $messageContent);
            $str= str_replace(array_keys($numberMap), $numberMap, $str);
            
            if($sentThrough==0){
                $str = str_ireplace( array('.','=','~','@','#','%','$','^','&','*',',','_','+', '\'', '"', ',' , ';', '<', '>' ), '', $str);
            } else {
                $str = str_ireplace( array('.','/','-','=','~','@','#','%','$','^','&','*',',','_','+', '\'', '"', ',' , ';', '<', '>' ), '', $str);
            }

            $years = array(2022,2023);
            preg_match_all('!\d+!', $str, $matches);
            foreach($matches[0] as $numericText){
                if(strlen($numericText)>=4 and strlen($numericText)<=8){
                    if(!in_array($numericText, $years)){
                        return $response->withJson([
                            'status'    => "error",
                            'message'   => 'Your sms contains number(s) like OTP and you are not allowed to send OTP. Please change the sms content or contact support.',
                        ], 200);
                        exit;
                    }
                }
            }
        }
        

        $this->messagetype = SmsSystm::getMessageType($request->getparam('message'));

        if ($this->messagetype=='unicode') {
                $is_unicode = 1;
        } else {
            $is_unicode = 0;
        }

        

        

        $currentBalance = 0;
        $totalmaskbal = $user->mask_balance;
        $totalnonmaskbal = $user->nonmask_balance;
        $totalvoicebal = $user->voice_balance;

        $root_userid = $user->root_user_id;

        $reseller_id = $user->reseller_id;

        $category = $senderId->type;

        if ($senderId->type==2) {
            $senderidType = 'nomask';
            $currentBalance = $user->nonmask_balance;
        } else if ($senderId->type==1) {
            $senderidType = 'mask';
            $currentBalance = $user->mask_balance;
        }

        $totalSms = 0;

        if ($request->getParam('numbertype')) {
            $numberType = $request->getParam('numbertype');
        } else {
            $numberType = 'single';
        }

        

        //Single contact number start
        if ($numberType == 'single') {
            if (!$request->getParam('contact_number')) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'contact_number is required parameter',
                ], 200);
            }

            $contactlist = is_array($request->getParam('contact_number')) ? $request->contact_number : explode(",", str_replace("\n", ",", str_replace(" ", ",", $request->getParam('contact_number'))));

            //get valid contacts from this array of numbers
            $contacts = array();
            foreach ($contactlist as $mobileNo) {
                if ($mobileNo = ContactsAndGroups::mobileNumber($mobileNo)) {
                    array_push($contacts, $mobileNo);
                }
            }

            if (isset($contacts[0])) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
            } else {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Invalid format of contact numbers. Please check your cotact numbers',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            $totalSms = $this->messagecount*$this->totalValidContact;

            if ($totalmaskbal < $totalSms && $senderidType == 'mask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient mask sms balance',
                ], 200);
            }

            if ($totalnonmaskbal < $totalSms && $senderidType == 'nomask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient nonmask sms balance',
                ], 200);
            }

            if ($totalvoicebal < $totalSms && $senderidType == 'voice') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient voice sms balance',
                ], 200);
            }
        } else {
            $contactGroup = explode(',', $request->getParam('contactgroup'));
            
            $contacts = $numberType == 'contgroup' ? $this->getValidNumbers($userId, $contactGroup) : $this->validMobileFromFile($userId, $request);

            if (isset($contacts)) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
            }

            if (!$contacts) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'There is an error, problem may be invalid file format!',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            $totalSms = ($this->totalValidContact*$this->messagecount);

            if ($totalmaskbal < $totalSms && $senderidType == 'mask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient mask sms balance',
                ], 200);
            }

            if ($totalnonmaskbal < $totalSms && $senderidType == 'nomask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient nonmask sms balance',
                ], 200);
            }

            if ($totalvoicebal < $totalSms && $senderidType == 'voice') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient voice sms balance',
                ], 200);
            }
        }

        $newBalance = $currentBalance - $totalSms;

        //deduce the user's balance
        UserBalanceManager::updateBalance($user->id, $category, $newBalance);


        // print_r($contacts); die();

        //here is the array of numbers in integer formatted

        /*
        * if total numbers are within 5 and no campaign request then we will take the requested numbers as individual numbers
        * but if the total numbers exceeds 5 and there is no campaign request then we will convert it to a campaign
        * if there is any campaign request then we will take the equested numbers as campaign
        */
        if ($this->totalValidContact>1) {
            /*
            * request is a campaign.
            * process it as a campaign
            *
            * ----------Parameters------
            * campaign_name
            * campaign_description
            * scheduled
            * target_time
            *
            */
            
            $campaignId = date('y') .$user->id. date('mdhis'). mt_rand(100, 999);

            $campaignName = "";
            if ($request->getParam('campaign_name')) {
                $campaignName = $request->getParam('campaign_name');
            } else {
                $campaignName = "Campaign ". $campaignId;
            }

            $campaignStatus = 'Pending';
            if ($isScheduled==1) {
                $campaignStatus = 'Scheduled';
            }

            $numbersCount = count($contacts);
            if ($numbersCount<50) {
                $campaignType = "A";
            } else if ($numbersCount<500) {
                $campaignType = "B";
            } else if ($numbersCount<3000) {
                $campaignType = "C";
            } else if ($numbersCount<5000) {
                $campaignType = "D";
            } else {
                $campaignType = "E";
            }

            $campaign = SmsCampaigns::create([
                'campaign_type'         => $campaignType,
                'campaign_no'           => $campaignId,
                'campaign_name'         => $campaignName,
                'campaign_description'  => $request->getParam('campaign_description'),
                'user_id'               => $user->id,
                'sender_id'             => $senderId->id,
                'is_unicode'            => $is_unicode,
                'category'              => $category,
                'content'               => $message,
                'sms_qty'               => $this->messagecount,
                'total_numbers'         => $this->totalValidContact,
                'sent_through'          => $sentThrough,//web
                'is_scheduled'          => $isScheduled,
                'scheduled_time'        => $scheduledTime,
                'status'                => $campaignStatus,
                'active'                => 1,

            ]);

            //create the account transaction
            $transaction = AccountTransaction::create([
                'type'        => $category,
                'user'        => $user->id,
                'txn_type'    => $senderidType.'_campaign',
                'reference'   => $campaign->id,
                'debit'       => $totalSms,
                'credit'      => 0,
                'balance'     => $newBalance,
                'note'        => 'Campaign created',
                'active'      => 1,
            ]);


            //check if MNP Dipping is enabled
            if ($user->live_dipping == true) {
                //process each number's operator checking thow MNP Dipping API

                $wrongNumberCount = 0;
                $wrongNumberKeys = array();
                
                //
                //checking campaign type outside the loop increases performance
                //
                
                if ($campaignType=='A') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersA::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='B') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersB::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='C') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersC::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='D') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersD::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='E') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersE::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                }



                if ($wrongNumberCount>0) {
                    //credit the wrong numbers smscount
                    $creditWrongNumbers = $wrongNumberCount * $this->messagecount;
                    $newBalance = $newBalance +$creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);
                    
                    //create the account transaction
                    $transaction = AccountTransaction::create([
                        'type'        => $category,
                        'user'        => $user->id,
                        'txn_type'    => $senderidType.'_campaign',
                        'reference'   => $campaign->id,
                        'debit'       => 0,
                        'credit'      => $creditWrongNumbers,
                        'balance'     => $newBalance,
                        'note'        => $wrongNumberCount. ' wrong numbers in campaign',
                        'active'      => 1,
                    ]);

                    //remove the numbers from contacts
                    foreach ($wrongNumberKeys as $itemKey) {
                        unset($contacts[$itemKey]);
                        // $contacts = array_values($contacts); //if need to reindex
                    }
                }
                //end of wrong numbers processing

            //mnp dipping ends
            } else {
                //if mnp dipping is disabled

                //
                //checking campaign type outside the loop increases performance
                //
                
                if ($campaignType=='A') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersA::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='B') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersB::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='C') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersC::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='D') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersD::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='E') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersE::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                }
                //insertion complete

                //set operator by prefix

                if ($campaignType=='A') {
                    $campaignNumbersTable = 'sms_campaign_numbers';
                } else if ($campaignType=='B') {
                    $campaignNumbersTable = 'sms_campaign_numbersB';
                } else if ($campaignType=='C') {
                    $campaignNumbersTable = 'sms_campaign_numbersC';
                } else if ($campaignType=='D') {
                    $campaignNumbersTable = 'sms_campaign_numbersD';
                } else if ($campaignType=='E') {
                    $campaignNumbersTable = 'sms_campaign_numbersE';
                }

                $db_conx = $this->DbConnect->connect();
                //gp
                $sql = "UPDATE $campaignNumbersTable SET `operator`=1 WHERE campaign_id=$campaign->id AND number LIKE '17%' OR number LIKE '13%'";
                $query = mysqli_query($db_conx, $sql);

                //bl
                $sql = "UPDATE $campaignNumbersTable SET `operator`=2 WHERE campaign_id=$campaign->id AND number LIKE '19%' OR number LIKE '14%'";
                $query = mysqli_query($db_conx, $sql);

                //airtel
                $sql = "UPDATE $campaignNumbersTable SET `operator`=3 WHERE campaign_id=$campaign->id AND number LIKE '16%'";
                $query = mysqli_query($db_conx, $sql);

                //robi
                $sql = "UPDATE $campaignNumbersTable SET `operator`=4 WHERE campaign_id=$campaign->id AND number LIKE '18%'";
                $query = mysqli_query($db_conx, $sql);

                //telealk
                $sql = "UPDATE $campaignNumbersTable SET `operator`=5 WHERE campaign_id=$campaign->id AND number LIKE '15%'";
                $query = mysqli_query($db_conx, $sql);
                //operator processing complete

            //non dipping end
            }

            if ($request->getParam('process_type')) {
                if ($request->getParam('process_type')=='async') {
                    // return the campaign id and asynchronously process the operator processing

                   $this->fastRequest($this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token'));

                    return $response->withJson([
                        'status'    => "campaign_started",
                        'message'   => "Campaign sms processing started...",
                        'url'   => $this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token'),
                        'campaign'  => $campaign->id
                    ], 200);
                }
            }

            // process operator wise processing
            $totalSuccessCount = 0;
            $totalFailedCount = 0;

            //start operator processing of campaign

            $outputUrls = [];
            $senderidGateways = [];

            $numberStatus = 0; //pending numbers initially

            for ($i=1; $i <=5; $i++) {
                $inputOperator = $i;

                if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $inputOperator)) {
                    if ($senderidgatewayInfo->output_operator ==1) {
                        // Grameenphon

                        $outputUrls[$inputOperator] = "/internal/process-gp";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==2) {
                        // Banglalink

                        $outputUrls[$inputOperator] = "/internal/process-bl";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==4) {
                        //there is no gateway for Airtel. gateway is robi. so output operator can not be 3

                        // Airtel/Robi

                        $outputUrls[$inputOperator] = "/internal/process-robi";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==5) {
                        // TeleTalk

                        $outputUrls[$inputOperator] = "/internal/process-ttk";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==12) {
                        // custom2

                        $outputUrls[$inputOperator] = "/internal/process-custom2";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    }  else if ($senderidgatewayInfo->output_operator ==13) {
                        // custom3

                        $outputUrls[$inputOperator] = "/internal/process-custom3";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    }
                }
            }


            $client = new Client(['base_uri' => $this->baseUrl]);

            // Initiate each request but do not block
            $promises = [
                'gp'    => $client->getAsync($outputUrls[1], [
                    'query' => [
                            "api_token"         => $this->apiToken,
                            "campaign"          => $campaign->id,
                            "numberStatus"      => $numberStatus,
                            "senderidGateway"   => $senderidGateways[1]
                        ]
                    ]),

                'bl'   => $client->getAsync($outputUrls[2], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[2]
                        ]
                    ]),

                'airtel'   => $client->getAsync($outputUrls[3], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[3]
                        ]
                    ]),
                'robi'   => $client->getAsync($outputUrls[4], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[4]
                        ]
                    ]),

                'ttk'   => $client->getAsync($outputUrls[5], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[5]
                        ]
                    ]),
                'custom'   => $client->getAsync($outputUrls[11], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[11]
                            ]
                        ]),
                'metrotel'   => $client->getAsync($outputUrls[12], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaignId,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[12]
                        ]
                    ]),
                'fusion'   => $client->getAsync($outputUrls[13], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaignId,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[13]
                        ]
                    ]),
            ];

            // Wait for the requests to complete, even if some of them fail
            $responses = Promise\Utils::settle($promises)->wait();

            $gpResponse      = json_decode($responses['gp']['value']->getBody());
            $blResponse      = json_decode($responses['bl']['value']->getBody());
            $airtelResponse  = json_decode($responses['airtel']['value']->getBody());
            $robiResponse    = json_decode($responses['robi']['value']->getBody());
            $ttkResponse     = json_decode($responses['ttk']['value']->getBody());
            $customResponse  = json_decode($responses['custom']['value']->getBody());
            $custom2Response  = json_decode($responses['metrotel']['value']->getBody());
            $custom3Response  = json_decode($responses['fusion']['value']->getBody());


            if ($gpResponse!="failed") {
                $totalSuccessCount += $gpResponse->successCount;
                $totalFailedCount  += $gpResponse->failedCount;
            }
            if ($blResponse!="failed") {
                $totalSuccessCount += $blResponse->successCount;
                $totalFailedCount  += $blResponse->failedCount;
            }
            if ($airtelResponse!="failed") {
                $totalSuccessCount += $airtelResponse->successCount;
                $totalFailedCount  += $airtelResponse->failedCount;
            }
            if ($robiResponse!="failed") {
                $totalSuccessCount += $robiResponse->successCount;
                $totalFailedCount  += $robiResponse->failedCount;
            }
            if ($ttkResponse!="failed") {
                $totalSuccessCount += $ttkResponse->successCount;
                $totalFailedCount  += $ttkResponse->failedCount;
            }
            if ($customResponse!="failed") {
                $totalSuccessCount += $customResponse->successCount;
                $totalFailedCount  += $customResponse->failedCount;
            }
            if ($custom2Response!="failed") {
                $totalSuccessCount += $custom2Response->successCount;
                $totalFailedCount  += $custom2Response->failedCount;
            }
            if ($custom3Response!="failed") {
                $totalSuccessCount += $custom3Response->successCount;
                $totalFailedCount  += $custom3Response->failedCount;
            }

            //end operator processing of campaign

            $campaign->status = 'Complete';
            $campaign->save();

            $delayTime = 300;


            //process refund here
            //get failed numbers in the campaign
            if ($campaignType=='A') {
                $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 300;
            } else if ($campaignType=='B') {
                $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 900;
            } else if ($campaignType=='C') {
                $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 2400;
            } else if ($campaignType=='D') {
                $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 3600;
            } else if ($campaignType=='E') {
                $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 7200;
            }





            if ($failedSmsCount>0) {
                //campaign has failed number

                $user = Users::find($campaign->user_id);
                if ($senderId->type==2) {
                    $currentBalance = $user->nonmask_balance;
                } else if ($senderId->type==1) {
                    $currentBalance = $user->mask_balance;
                }
                $creditFailedCount = $failedSmsCount * $campaign->sms_qty;




                
                //no chance of having any old refund for the campaign because this is the sms sending api request
                
                $newBalance = $currentBalance + $creditFailedCount;

                //update the user's balance
                UserBalanceManager::updateBalance($user->id, $category, $newBalance);


                //create the account transaction
                $transaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_cam_refund',
                    'reference'   => $campaign->id,
                    'debit'       => 0,
                    'credit'      => $creditFailedCount,
                    'balance'     => $newBalance,
                    'note'        => $failedSmsCount. ' numbers failed in campaign',
                    'active'      => 1,
                ]);

                //new entry created
                //refund done

                //update failed sms count in campaign table
                $campaign->failed_sms = $creditFailedCount;
                $campaign->save();
            }

            // asynchronous call for fetching delivery request
            $this->fastRequest($this->baseUrl.'internal/campaign-dl-report?api_token='.$this->apiToken.'&campaignId='.$campaign->id.'&delayTime='.$delayTime);

            return $response->withJson([
                'status'    => "success",
                'message'   => "Campaign sms sent successfully",
                'success'   => $totalSuccessCount,
                'failed'    => $totalFailedCount,
            ], 200);
        } else {
            //not a campaign or single number request

            $mobile = $contacts[0];

            $msgStatus = 'Pending';
            if ($isScheduled==1) {
                $msgStatus = 'Scheduled';
            }

            // check if already a transaction is created or not
            $accTransaction = AccountTransaction::where('user', $user->id)->where('type', $category)->where('txn_type', $senderidType.'_single')->where('credit', 0)->whereDate('created_at', '=', date('Y-m-d'))->first();

            if ($accTransaction) {
                // increase the transaction units amount by number of sms
                $accTransaction->debit = $accTransaction->debit+$totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();
            } else {
                 //no transaction of the user for the day

                //create the account transaction
                $accTransaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_single',
                    'debit'       => $totalSms,
                    'credit'      => 0,
                    'balance'     => $newBalance,
                    'note'        => 'Individual sms sent through web', //web or api
                    'active'      => 1,
                ]);
            }

            //generate message Id
            $lastMsgId = null;

            $lastMsg = SmsSingle::orderBy('id', 'desc')->first();
            if ($lastMsg) {
                $lastMsgId = $lastMsg->id;
                $lastMsgId++;
            } else {
                $lastMsgId = 1;
            }
            $smsId = date('ymd') .$user->id. $lastMsgId. mt_rand(10000, 99099);

            $operatorId = null;

            //check if MNP Dipping is enabled
            if ($user->live_dipping == true) {
                //process number's operator checking thow MNP Dipping API

                // check each numbers through MNP dipping
                    
                if ($optId = SmsSystm::checkMnpOperator($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }


            //mnp dipping ends
            } else {
                //if mnp dipping is disabled

                // check operator by prefix
                if ($optId = SmsSystm::getOperatorByPrefix($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    
                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }
                

            //non dipping end
            }

            
            // insert the number to individual-numbers
            $smsSingle = SmsSingle::create([
                'sms_id'            => $smsId,
                'user_id'           => $user->id,
                'sender_id'         => $senderId->id,
                'category'          => $category,
                'number'            => $mobile,
                'operator'          => $operatorId,
                'is_unicode'        => $is_unicode,
                'content'           => $message,
                'qty'               => $this->messagecount,
                'sent_through'      => $sentThrough, //0=web, 1=api
                'is_scheduled'      => $isScheduled,
                'scheduled_time'    => $scheduledTime,
                'status'            => 0,
                'active'            => 1,
            ]);

            // $number = 55744;
            // $alphanumeric = $this->convertNumberToAlphanumeric($number);

            // dd($alphanumeric);

            // $alphanumeric = "AHDNKS";
            // $number = $this->convertAlphanumericToNumber($alphanumeric);
            // dd($number);



            // insertion complete
            $weblink = true;
            if($weblink){
                //sms content number to hex string
                // $hex = dechex($smsSingle->content);
                $alphanumeric = $this->convertNumberToAlphanumeric($smsSingle->content);
                
                $smsSingle->content = "http://login.bdlists.com/".$alphanumeric."/weblink"; 
                $smsSingle->save();
            }

            // dd($smsSingle->content);


            // process operator wise processing
            if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $operatorId)) {
                if ($senderidgatewayInfo->output_operator ==1) {
                    // Grameenphon
                    $operatorResponse = GP::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==2) {
                    // Banglalink
                    $operatorResponse = Banglalink::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==3 || $senderidgatewayInfo->output_operator ==4) {
                    // Airtel/Robi
                    $operatorResponse = Robi::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==5) {
                    // TeleTalk
                    $operatorResponse = Teletalk::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 11) {
                    // custom operator
                    $operatorResponse = CustomOperator::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 12) {
                    // custom operator 2
                    $operatorResponse = CustomOperator2::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 13) {
                    // custom operator 3
                    $operatorResponse = CustomOperator3::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                }

            }

            if ($operatorResponse == 'success') {
                return $response->withJson([
                    'status'    => "success",
                    'message'   => "SMS sent successfully",
                    'smsid'     => $smsId,
                    'SmsCount'  => $smsSingle->qty,
                    // 'gateway' => $senderidgatewayInfo,
                    // 'apiResponse' => $operatorResponse
                ], 200);

            } else {
                self::sendSMSweblink($request, $response);

                return $response->withJson([
                    'status'    => "success",
                    'message'   => "SMS sent successfully",
                    'smsid'     => $smsId,
                    'SmsCount'  => $smsSingle->qty,
                    // 'gateway' => $senderidgatewayInfo,
                    // 'apiResponse' => $operatorResponse
                ], 200);
                //process refund here
                $newBalance = $currentBalance + $totalSms;

                //deduce the user's balance
                UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                //refund to transaction
                $accTransaction->debit = $accTransaction->debit + $totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();

                //refund done

                
                return $response->withJson([
                        'status'    => "error",
                        'message'   => $operatorResponse,
                    ], 200);
            }

            //System Error
            //process refund here
            $newBalance = $currentBalance + $totalSms;

            //deduce the user's balance
            UserBalanceManager::updateBalance($user->id, $category, $newBalance);

            //refund to transaction
            $accTransaction->debit = $accTransaction->debit + $totalSms;
            $accTransaction->balance = $accTransaction->balance + $totalSms;
            $accTransaction->save();

            //refund done


            return $response->withJson([
                'status'    => "error",
                'message'   => 'System Error',
            ], 200);
        }
        //end of individual

        return $response->withJson([
            'status'    => "error",
            'message'   => 'System Error',
        ], 200);
    }


    //divider api
    public function sendSMSdivider($request, $response)
    {
        
        error_reporting(0);

        // if (!$request->getParam('message') ||!$request->getParam('senderid')||!$request->getParam('api_token')) 
        if (!$request->getParam('message') ||!$request->getParam('api_token')) 
        {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Required field is empty, message content & senderid are required parameter',
            ], 200);
        }

        $api_token = $request->getParam('api_token');

        $sentThrough = 1;
        if ($request->getParam('source')) {
            $sentThrough = 0;
        }
        
        
        //get the user
        $user = $this->auth->user($api_token);

        if ($user->status == 'n') {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'We have found unethical transection from your account, your account is blocked until the issue is solve. Please contact support team.',
            ], 200);
        }

        $userId = $user->id;

        //new modified Rakibul

        $clientSenderids = SenderidUsers::where('user', $userId)->get();
        // dd($clientSenderids);

        $clientSenderidsArr=[];
        foreach ($clientSenderids as $senderId){
            $clientSenderidsArr[]= $senderId->getSenderid->name;
        }

        // $clientSenderidsArr = $request->getParam('senderidsArr');
        $clientSenderidsArr = htmlspecialchars(implode(',', $clientSenderidsArr));
        $clientSenderidsArr = explode(',',$clientSenderidsArr);
        // dd($clientSenderidsArr);

        // $clientSenderidsArr=["BoiBitan","SMSinBD", "DataHost IT"];

        $arrayLength = count($clientSenderidsArr);

        $randomNumber = mt_rand(0, $arrayLength);
        $clientSenderid =  $clientSenderidsArr[$randomNumber];

        // dd($clientSenderid);


        $message = $request->getParam('message');
        $schedule = $request->getParam('schedule');
        $scheduledTime = $request->getParam('target_time');

        $isScheduled = 0;
        if ($schedule && $schedule==1) {
            $isScheduled = 1;
        }

        $senderIdName = $request->getParam('senderid');

        //get master senderId
        $senderId = SenderIdDetails::getSenderIdByNameIfValid($userId, $senderIdName);

        if (!$senderId) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Invalid Sender ID.',
            ], 200);
        }
        



        $contacts = [];


        //the condition block is used for debugging codes without affecting the system
        if($request->getParam('debug') && $request->getParam('debug')==1){

            // var_dump(Str::length($request->getparam('message'))); //strlen($message);

            // if (strlen($request->getparam('message')) != strlen(utf8_decode($request->getparam('message'))))
            // {
            //     echo 'unicode';
            // } else {
            //     echo 'text';                
            // }

            // $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

            // if (!$this->messagecount) {
            //     return $response->withJson([
            //         'status'    => "error",
            //         'message'   => 'SMS length exceeded max limit of 8 sms.',
            //     ], 200);
            // }
        }

        $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

        if (!$this->messagecount) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'SMS length exceeded max limit of 8 sms.',
            ], 200);
        }

        // OTP restriction
        if($user->otp_allowed!=1){

            $messageContent = $request->getparam('message');

            $numberMap = [
                '০' => 0,
                '১' => 1,
                '২' => 2,
                '৩' => 3,
                '৪' => 4,
                '৫' => 5,
                '৬' => 6,
                '৭' => 7,
                '৮' => 8,
                '৯' => 9,
            ];

            $str = str_replace(' ', '', $messageContent);
            $str= str_replace(array_keys($numberMap), $numberMap, $str);
            
            if($sentThrough==0){
                $str = str_ireplace( array('.','=','~','@','#','%','$','^','&','*',',','_','+', '\'', '"', ',' , ';', '<', '>' ), '', $str);
            } else {
                $str = str_ireplace( array('.','/','-','=','~','@','#','%','$','^','&','*',',','_','+', '\'', '"', ',' , ';', '<', '>' ), '', $str);
            }

            $years = array(2022,2023);
            preg_match_all('!\d+!', $str, $matches);
            foreach($matches[0] as $numericText){
                if(strlen($numericText)>=4 and strlen($numericText)<=8){
                    if(!in_array($numericText, $years)){
                        return $response->withJson([
                            'status'    => "error",
                            'message'   => 'Your sms contains number(s) like OTP and you are not allowed to send OTP. Please change the sms content or contact support.',
                        ], 200);
                        exit;
                    }
                }
            }
        }
        

        $this->messagetype = SmsSystm::getMessageType($request->getparam('message'));

        if ($this->messagetype=='unicode') {
                $is_unicode = 1;
        } else {
            $is_unicode = 0;
        }

        

        

        $currentBalance = 0;
        $totalmaskbal = $user->mask_balance;
        $totalnonmaskbal = $user->nonmask_balance;
        $totalvoicebal = $user->voice_balance;

        $root_userid = $user->root_user_id;

        $reseller_id = $user->reseller_id;

        $category = $senderId->type;

        if ($senderId->type==2) {
            $senderidType = 'nomask';
            $currentBalance = $user->nonmask_balance;
        } else if ($senderId->type==1) {
            $senderidType = 'mask';
            $currentBalance = $user->mask_balance;
        }

        $totalSms = 0;

        if ($request->getParam('numbertype')) {
            $numberType = $request->getParam('numbertype');
        } else {
            $numberType = 'single';
        }

        

        //Single contact number start
        if ($numberType == 'single') {
            if (!$request->getParam('contact_number')) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'contact_number is required parameter',
                ], 200);
            }

            $contactlist = is_array($request->getParam('contact_number')) ? $request->contact_number : explode(",", str_replace("\n", ",", str_replace(" ", ",", $request->getParam('contact_number'))));

            //get valid contacts from this array of numbers
            $contacts = array();
            foreach ($contactlist as $mobileNo) {
                if ($mobileNo = ContactsAndGroups::mobileNumber($mobileNo)) {
                    array_push($contacts, $mobileNo);
                }
            }

            if (isset($contacts[0])) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
            } else {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Invalid format of contact numbers. Please check your cotact numbers',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            $totalSms = $this->messagecount*$this->totalValidContact;

            if ($totalmaskbal < $totalSms && $senderidType == 'mask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient mask sms balance',
                ], 200);
            }

            if ($totalnonmaskbal < $totalSms && $senderidType == 'nomask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient nonmask sms balance',
                ], 200);
            }

            if ($totalvoicebal < $totalSms && $senderidType == 'voice') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient voice sms balance',
                ], 200);
            }
        } else {
            $contactGroup = explode(',', $request->getParam('contactgroup'));
            
            $contacts = $numberType == 'contgroup' ? $this->getValidNumbers($userId, $contactGroup) : $this->validMobileFromFile($userId, $request);

            if (isset($contacts)) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
            }

            if (!$contacts) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'There is an error, problem may be invalid file format!',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            $totalSms = ($this->totalValidContact*$this->messagecount);

            if ($totalmaskbal < $totalSms && $senderidType == 'mask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient mask sms balance',
                ], 200);
            }

            if ($totalnonmaskbal < $totalSms && $senderidType == 'nomask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient nonmask sms balance',
                ], 200);
            }

            if ($totalvoicebal < $totalSms && $senderidType == 'voice') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient voice sms balance',
                ], 200);
            }
        }

        $newBalance = $currentBalance - $totalSms;

        //deduce the user's balance
        UserBalanceManager::updateBalance($user->id, $category, $newBalance);


        // print_r($contacts); die();

        //here is the array of numbers in integer formatted

        /*
        * if total numbers are within 5 and no campaign request then we will take the requested numbers as individual numbers
        * but if the total numbers exceeds 5 and there is no campaign request then we will convert it to a campaign
        * if there is any campaign request then we will take the equested numbers as campaign
        */
        if ($this->totalValidContact>1) {
            /*
            * request is a campaign.
            * process it as a campaign
            *
            * ----------Parameters------
            * campaign_name
            * campaign_description
            * scheduled
            * target_time
            *
            */
            
            $campaignId = date('y') .$user->id. date('mdhis'). mt_rand(100, 999);

            $campaignName = "";
            if ($request->getParam('campaign_name')) {
                $campaignName = $request->getParam('campaign_name');
            } else {
                $campaignName = "Campaign ". $campaignId;
            }

            $campaignStatus = 'Pending';
            if ($isScheduled==1) {
                $campaignStatus = 'Scheduled';
            }

            $numbersCount = count($contacts);
            if ($numbersCount<50) {
                $campaignType = "A";
            } else if ($numbersCount<500) {
                $campaignType = "B";
            } else if ($numbersCount<3000) {
                $campaignType = "C";
            } else if ($numbersCount<5000) {
                $campaignType = "D";
            } else {
                $campaignType = "E";
            }

            $campaign = SmsCampaigns::create([
                'campaign_type'         => $campaignType,
                'campaign_no'           => $campaignId,
                'campaign_name'         => $campaignName,
                'campaign_description'  => $request->getParam('campaign_description'),
                'user_id'               => $user->id,
                'sender_id'             => $senderId->id,
                'is_unicode'            => $is_unicode,
                'category'              => $category,
                'content'               => $message,
                'sms_qty'               => $this->messagecount,
                'total_numbers'         => $this->totalValidContact,
                'sent_through'          => $sentThrough,//web
                'is_scheduled'          => $isScheduled,
                'scheduled_time'        => $scheduledTime,
                'status'                => $campaignStatus,
                'active'                => 1,

            ]);

            //create the account transaction
            $transaction = AccountTransaction::create([
                'type'        => $category,
                'user'        => $user->id,
                'txn_type'    => $senderidType.'_campaign',
                'reference'   => $campaign->id,
                'debit'       => $totalSms,
                'credit'      => 0,
                'balance'     => $newBalance,
                'note'        => 'Campaign created',
                'active'      => 1,
            ]);


            //check if MNP Dipping is enabled
            if ($user->live_dipping == true) {
                //process each number's operator checking thow MNP Dipping API

                $wrongNumberCount = 0;
                $wrongNumberKeys = array();
                
                //
                //checking campaign type outside the loop increases performance
                //
                
                if ($campaignType=='A') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersA::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='B') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersB::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='C') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersC::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='D') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersD::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                } else if ($campaignType=='E') {
                    // check each numbers through MNP dipping
                    foreach ($contacts as $key => $mobile) {
                        if ($operatorId = SmsSystm::checkMnpOperator($mobile)) {
                            //insert the number to campaign-numbers
                            SmsCampaignNumbersE::create([
                                'campaign_id'     => $campaign->id,
                                'number'          => $mobile,
                                'operator'        => $operatorId,
                                'status'          => 0,
                            ]);
                        } else {
                            $wrongNumberCount++;
                            array_push($wrongNumberKeys, $key);
                        }
                    }
                }



                if ($wrongNumberCount>0) {
                    //credit the wrong numbers smscount
                    $creditWrongNumbers = $wrongNumberCount * $this->messagecount;
                    $newBalance = $newBalance +$creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);
                    
                    //create the account transaction
                    $transaction = AccountTransaction::create([
                        'type'        => $category,
                        'user'        => $user->id,
                        'txn_type'    => $senderidType.'_campaign',
                        'reference'   => $campaign->id,
                        'debit'       => 0,
                        'credit'      => $creditWrongNumbers,
                        'balance'     => $newBalance,
                        'note'        => $wrongNumberCount. ' wrong numbers in campaign',
                        'active'      => 1,
                    ]);

                    //remove the numbers from contacts
                    foreach ($wrongNumberKeys as $itemKey) {
                        unset($contacts[$itemKey]);
                        // $contacts = array_values($contacts); //if need to reindex
                    }
                }
                //end of wrong numbers processing

            //mnp dipping ends
            } else {
                //if mnp dipping is disabled

                //
                //checking campaign type outside the loop increases performance
                //
                
                if ($campaignType=='A') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersA::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='B') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersB::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='C') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersC::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='D') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersD::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                } else if ($campaignType=='E') {
                    foreach ($contacts as $key => $mobile) {
                        //insert the number to campaign-numbers
                        SmsCampaignNumbersE::create([
                            'campaign_id'     => $campaign->id,
                            'number'          => $mobile,
                            // 'operator'        => , by default null
                            'status'          => 0,
                        ]);
                    }
                }
                //insertion complete

                //set operator by prefix

                if ($campaignType=='A') {
                    $campaignNumbersTable = 'sms_campaign_numbers';
                } else if ($campaignType=='B') {
                    $campaignNumbersTable = 'sms_campaign_numbersB';
                } else if ($campaignType=='C') {
                    $campaignNumbersTable = 'sms_campaign_numbersC';
                } else if ($campaignType=='D') {
                    $campaignNumbersTable = 'sms_campaign_numbersD';
                } else if ($campaignType=='E') {
                    $campaignNumbersTable = 'sms_campaign_numbersE';
                }

                $db_conx = $this->DbConnect->connect();
                //gp
                $sql = "UPDATE $campaignNumbersTable SET `operator`=1 WHERE campaign_id=$campaign->id AND number LIKE '17%' OR number LIKE '13%'";
                $query = mysqli_query($db_conx, $sql);

                //bl
                $sql = "UPDATE $campaignNumbersTable SET `operator`=2 WHERE campaign_id=$campaign->id AND number LIKE '19%' OR number LIKE '14%'";
                $query = mysqli_query($db_conx, $sql);

                //airtel
                $sql = "UPDATE $campaignNumbersTable SET `operator`=3 WHERE campaign_id=$campaign->id AND number LIKE '16%'";
                $query = mysqli_query($db_conx, $sql);

                //robi
                $sql = "UPDATE $campaignNumbersTable SET `operator`=4 WHERE campaign_id=$campaign->id AND number LIKE '18%'";
                $query = mysqli_query($db_conx, $sql);

                //telealk
                $sql = "UPDATE $campaignNumbersTable SET `operator`=5 WHERE campaign_id=$campaign->id AND number LIKE '15%'";
                $query = mysqli_query($db_conx, $sql);
                //operator processing complete

            //non dipping end
            }

            if ($request->getParam('process_type')) {
                if ($request->getParam('process_type')=='async') {
                    // return the campaign id and asynchronously process the operator processing

                   $this->fastRequest($this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token'));

                    return $response->withJson([
                        'status'    => "campaign_started",
                        'message'   => "Campaign sms processing started...",
                        'url'   => $this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token'),
                        'campaign'  => $campaign->id
                    ], 200);
                }
            }

            // process operator wise processing
            $totalSuccessCount = 0;
            $totalFailedCount = 0;

            //start operator processing of campaign

            $outputUrls = [];
            $senderidGateways = [];

            $numberStatus = 0; //pending numbers initially

            for ($i=1; $i <=5; $i++) {
                $inputOperator = $i;

                if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $inputOperator)) {
                    if ($senderidgatewayInfo->output_operator ==1) {
                        // Grameenphon

                        $outputUrls[$inputOperator] = "/internal/process-gp";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==2) {
                        // Banglalink

                        $outputUrls[$inputOperator] = "/internal/process-bl";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==4) {
                        //there is no gateway for Airtel. gateway is robi. so output operator can not be 3

                        // Airtel/Robi

                        $outputUrls[$inputOperator] = "/internal/process-robi";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==5) {
                        // TeleTalk

                        $outputUrls[$inputOperator] = "/internal/process-ttk";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    } else if ($senderidgatewayInfo->output_operator ==12) {
                        // custom2

                        $outputUrls[$inputOperator] = "/internal/process-custom2";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    }  else if ($senderidgatewayInfo->output_operator ==13) {
                        // custom3

                        $outputUrls[$inputOperator] = "/internal/process-custom3";
                        $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                    }
                }
            }


            $client = new Client(['base_uri' => $this->baseUrl]);

            // Initiate each request but do not block
            $promises = [
                'gp'    => $client->getAsync($outputUrls[1], [
                    'query' => [
                            "api_token"         => $this->apiToken,
                            "campaign"          => $campaign->id,
                            "numberStatus"      => $numberStatus,
                            "senderidGateway"   => $senderidGateways[1]
                        ]
                    ]),

                'bl'   => $client->getAsync($outputUrls[2], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[2]
                        ]
                    ]),

                'airtel'   => $client->getAsync($outputUrls[3], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[3]
                        ]
                    ]),
                'robi'   => $client->getAsync($outputUrls[4], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[4]
                        ]
                    ]),

                'ttk'   => $client->getAsync($outputUrls[5], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaign->id,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[5]
                        ]
                    ]),
                'custom'   => $client->getAsync($outputUrls[11], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[11]
                            ]
                        ]),
                'metrotel'   => $client->getAsync($outputUrls[12], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaignId,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[12]
                        ]
                    ]),
                'fusion'   => $client->getAsync($outputUrls[13], [
                    'query' => [
                            "api_token"     => $this->apiToken,
                            "campaign"      => $campaignId,
                            "numberStatus"  => $numberStatus,
                            "senderidGateway"   => $senderidGateways[13]
                        ]
                    ]),
            ];

            // Wait for the requests to complete, even if some of them fail
            $responses = Promise\Utils::settle($promises)->wait();

            $gpResponse      = json_decode($responses['gp']['value']->getBody());
            $blResponse      = json_decode($responses['bl']['value']->getBody());
            $airtelResponse  = json_decode($responses['airtel']['value']->getBody());
            $robiResponse    = json_decode($responses['robi']['value']->getBody());
            $ttkResponse     = json_decode($responses['ttk']['value']->getBody());
            $customResponse  = json_decode($responses['custom']['value']->getBody());
            $custom2Response  = json_decode($responses['metrotel']['value']->getBody());
            $custom3Response  = json_decode($responses['fusion']['value']->getBody());


            if ($gpResponse!="failed") {
                $totalSuccessCount += $gpResponse->successCount;
                $totalFailedCount  += $gpResponse->failedCount;
            }
            if ($blResponse!="failed") {
                $totalSuccessCount += $blResponse->successCount;
                $totalFailedCount  += $blResponse->failedCount;
            }
            if ($airtelResponse!="failed") {
                $totalSuccessCount += $airtelResponse->successCount;
                $totalFailedCount  += $airtelResponse->failedCount;
            }
            if ($robiResponse!="failed") {
                $totalSuccessCount += $robiResponse->successCount;
                $totalFailedCount  += $robiResponse->failedCount;
            }
            if ($ttkResponse!="failed") {
                $totalSuccessCount += $ttkResponse->successCount;
                $totalFailedCount  += $ttkResponse->failedCount;
            }
            if ($customResponse!="failed") {
                $totalSuccessCount += $customResponse->successCount;
                $totalFailedCount  += $customResponse->failedCount;
            }
            if ($custom2Response!="failed") {
                $totalSuccessCount += $custom2Response->successCount;
                $totalFailedCount  += $custom2Response->failedCount;
            }
            if ($custom3Response!="failed") {
                $totalSuccessCount += $custom3Response->successCount;
                $totalFailedCount  += $custom3Response->failedCount;
            }

            //end operator processing of campaign

            $campaign->status = 'Complete';
            $campaign->save();

            $delayTime = 300;


            //process refund here
            //get failed numbers in the campaign
            if ($campaignType=='A') {
                $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 300;
            } else if ($campaignType=='B') {
                $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 900;
            } else if ($campaignType=='C') {
                $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 2400;
            } else if ($campaignType=='D') {
                $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 3600;
            } else if ($campaignType=='E') {
                $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->count();
                $delayTime = 7200;
            }





            if ($failedSmsCount>0) {
                //campaign has failed number

                $user = Users::find($campaign->user_id);
                if ($senderId->type==2) {
                    $currentBalance = $user->nonmask_balance;
                } else if ($senderId->type==1) {
                    $currentBalance = $user->mask_balance;
                }
                $creditFailedCount = $failedSmsCount * $campaign->sms_qty;




                
                //no chance of having any old refund for the campaign because this is the sms sending api request
                
                $newBalance = $currentBalance + $creditFailedCount;

                //update the user's balance
                UserBalanceManager::updateBalance($user->id, $category, $newBalance);


                //create the account transaction
                $transaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_cam_refund',
                    'reference'   => $campaign->id,
                    'debit'       => 0,
                    'credit'      => $creditFailedCount,
                    'balance'     => $newBalance,
                    'note'        => $failedSmsCount. ' numbers failed in campaign',
                    'active'      => 1,
                ]);

                //new entry created
                //refund done

                //update failed sms count in campaign table
                $campaign->failed_sms = $creditFailedCount;
                $campaign->save();
            }

            // asynchronous call for fetching delivery request
            $this->fastRequest($this->baseUrl.'internal/campaign-dl-report?api_token='.$this->apiToken.'&campaignId='.$campaign->id.'&delayTime='.$delayTime);

            return $response->withJson([
                'status'    => "success",
                'message'   => "Campaign sms sent successfully",
                'success'   => $totalSuccessCount,
                'failed'    => $totalFailedCount,
            ], 200);
        } else {
            //not a campaign or single number request

            $mobile = $contacts[0];

            $msgStatus = 'Pending';
            if ($isScheduled==1) {
                $msgStatus = 'Scheduled';
            }

            // check if already a transaction is created or not
            $accTransaction = AccountTransaction::where('user', $user->id)->where('type', $category)->where('txn_type', $senderidType.'_single')->where('credit', 0)->whereDate('created_at', '=', date('Y-m-d'))->first();

            if ($accTransaction) {
                // increase the transaction units amount by number of sms
                $accTransaction->debit = $accTransaction->debit+$totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();
            } else {
                 //no transaction of the user for the day

                //create the account transaction
                $accTransaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_single',
                    'debit'       => $totalSms,
                    'credit'      => 0,
                    'balance'     => $newBalance,
                    'note'        => 'Individual sms sent through web', //web or api
                    'active'      => 1,
                ]);
            }

            //generate message Id
            $lastMsgId = null;

            $lastMsg = SmsSingle::orderBy('id', 'desc')->first();
            if ($lastMsg) {
                $lastMsgId = $lastMsg->id;
                $lastMsgId++;
            } else {
                $lastMsgId = 1;
            }
            $smsId = date('ymd') .$user->id. $lastMsgId. mt_rand(10000, 99099);

            $operatorId = null;

            //check if MNP Dipping is enabled
            if ($user->live_dipping == true) {
                //process number's operator checking thow MNP Dipping API

                // check each numbers through MNP dipping
                    
                if ($optId = SmsSystm::checkMnpOperator($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }


            //mnp dipping ends
            } else {
                //if mnp dipping is disabled

                // check operator by prefix
                if ($optId = SmsSystm::getOperatorByPrefix($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    
                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }
                

            //non dipping end
            }

            
            // insert the number to individual-numbers
            $smsSingle = SmsSingle::create([
                'sms_id'            => $smsId,
                'user_id'           => $user->id,
                'sender_id'         => $senderId->id,
                'category'          => $category,
                'number'            => $mobile,
                'operator'          => $operatorId,
                'is_unicode'        => $is_unicode,
                'content'           => $message,
                'qty'               => $this->messagecount,
                'sent_through'      => $sentThrough, //0=web, 1=api
                'is_scheduled'      => $isScheduled,
                'scheduled_time'    => $scheduledTime,
                'status'            => 0,
                'active'            => 1,
            ]);
            // insertion complete
  
            //divider from database
            $divider = Divider::latest()->first();

            $is_divider = true;
            $dividerSymbol = $divider->divider;
            // dd($dividerSymbol);
            if($is_divider){
                //number to array so that join divider in loop
                $sms = "";
                $contentArr = str_split($smsSingle->content);

                foreach($contentArr as $value){
                    $sms.= $value.$dividerSymbol;
                }
                
                $smsSingle->content = substr($sms, 0, -1); 
                $smsSingle->save();
            }

            // dd($smsSingle->content);
            
            // process operator wise processing
            if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $operatorId)) {

                if ($senderidgatewayInfo->output_operator ==1) {
                    // Grameenphon
                    $operatorResponse = GP::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==2) {
                    // Banglalink
                    $operatorResponse = Banglalink::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==3 || $senderidgatewayInfo->output_operator ==4) {
                    // Airtel/Robi
                    $operatorResponse = Robi::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator ==5) {
                    // TeleTalk
                    $operatorResponse = Teletalk::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 11) {
                    // custom operator
                    $operatorResponse = CustomOperator::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 12) {
                    // custom operator 2
                    $operatorResponse = CustomOperator2::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                } else if ($senderidgatewayInfo->output_operator == 13) {
                    // custom operator 3
                    $operatorResponse = CustomOperator3::sendSingleMessage($smsSingle, $senderidgatewayInfo, $clientSenderid);
                }

            }

            // dd($operatorResponse);

            if ($operatorResponse == 'success') {
                return $response->withJson([
                    'status'    => "success",
                    'message'   => "SMS sent successfully",
                    'smsid'     => $smsId,
                    'SmsCount'  => $smsSingle->qty,
                    // 'gateway' => $senderidgatewayInfo,
                    // 'apiResponse' => $operatorResponse
                ], 200);

            } else {
                // self::sendSMSdivider($request, $response);

                return $response->withJson([
                    'status'    => "success",
                    'message'   => "SMS sent successfully",
                    'smsid'     => $smsId,
                    'SmsCount'  => $smsSingle->qty,
                    // 'gateway' => $senderidgatewayInfo,
                    // 'apiResponse' => $operatorResponse
                ], 200);
                //process refund here
                $newBalance = $currentBalance + $totalSms;

                //deduce the user's balance
                UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                //refund to transaction
                $accTransaction->debit = $accTransaction->debit + $totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();

                //refund done

                
                return $response->withJson([
                        'status'    => "error",
                        'message'   => $operatorResponse,
                    ], 200);
            }

            //System Error
            //process refund here
            $newBalance = $currentBalance + $totalSms;

            //deduce the user's balance
            UserBalanceManager::updateBalance($user->id, $category, $newBalance);

            //refund to transaction
            $accTransaction->debit = $accTransaction->debit + $totalSms;
            $accTransaction->balance = $accTransaction->balance + $totalSms;
            $accTransaction->save();

            //refund done


            return $response->withJson([
                'status'    => "error",
                'message'   => 'System Error',
            ], 200);
        }
        //end of individual

        return $response->withJson([
            'status'    => "error",
            'message'   => 'System Error',
        ], 200);
    }

    public function getValidNumbers($userId, $contactGroup)
    {

        $totalContacts = [];

        $newarr = [];
        if (! empty($contactGroup)) {
            foreach (array_unique($contactGroup) as $group) {
                $totalContacts[] = SmsSystm::getContactsInGroup($userId, $group);
            }
            
            foreach ($totalContacts as $contact) {
                array_push($newarr, $contact);
            }
            
            return array_merge(...$newarr);
        }

        return 0;
    }

    public function validMobileFromFile($userId, $request)
    {

        $totalContacts = [];

        $newarr = [];

        $file = ContactsAndGroups::addContactFile($userId, $request);

        $extension = $file['extension'];
        
        if ($extension === 'csv') {
            $contacts = ContactsAndGroups::getBdMobileNumberFromCSV($file);
        } else if ($extension === 'xls' || $extension === 'xlsx') {
            $contacts = ContactsAndGroups::getBDMobileNumberFromXlsOrXlsx($file);
        } else if ($extension === 'txt') {
            $contacts = ContactsAndGroups::getBDMobileNumberFromTextFile($file);
        } else {
            return false;
        }

        if (count($contacts) > 0) {
            return $contacts;
        }

        return 0;
    }

    //socket connection for asynchronous
    function fastRequest($url)
    {
        $parts=parse_url($url);
        $fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);
        $out = "GET ".$parts['path']. ((isset($parts['query'])) ? "?" . $parts['query'] : false) ." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Length: 0"."\r\n";
        $out.= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        fclose($fp);

        // echo "Connection";
    }

    public function processCampaignOperatorsms($request, $response, $args)
    {
        error_reporting(0);

        $campaignId = $args['id'];

        $campaign = SmsCampaigns::find($campaignId);
        if ($campaign) {
            if ($campaign->status == 'Pending' || null !==$request->getParam('failedretry')) {
                $senderId = SenderidMaster::find($campaign->sender_id);
                $user = Users::find($campaign->user_id);
                
                $numberStatus = 0;
                if ($request->getParam('failedretry')) {
                    $numberStatus = $request->getParam('failedretry');
                    if($request->getParam('failedretry'==3)){
                        $failedRetry = true;
                    }
                }

                //deduce the balance before processing for the failed sms
                if ($senderId->type==2) {
                    $senderidType = 'nonmask';
                    $currentBalance = $user->nonmask_balance;
                } else if ($senderId->type==1) {
                    $senderidType = 'mask';
                    $currentBalance = $user->mask_balance;
                }


                if($numberStatus==3){
                    //get failed numbers in the campaign
                    $campaignType = $campaign->campaign_type;
                    if ($campaignType=='A') {
                        $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    } else if ($campaignType=='B') {
                        $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    } else if ($campaignType=='C') {
                        $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    } else if ($campaignType=='D') {
                        $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    } else if ($campaignType=='E') {
                        $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    }

                    $totalFailedCount = $failedSmsCount * $campaign->sms_qty;
                    $newBalance = $currentBalance - $totalFailedCount;
                    $category = $campaign->category;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //create the account transaction
                    $transaction = AccountTransaction::create([
                        'type'        => $category,
                        'user'        => $user->id,
                        'txn_type'    => $senderidType.'_cam_retry',
                        'reference'   => $campaign->id,
                        'debit'       => $failedSmsCount,
                        'credit'      => 0,
                        'balance'     => $newBalance,
                        'note'        => $failedSmsCount. ' failed numbers retried in campaign',
                        'active'      => 1,
                     ]);
                }

                // process operator wise processing
                $totalSuccessCount = 0;
                $totalFailedCount = 0;

                //start operator processing of campaign

                $outputUrls = [];
                $senderidGateways = [];

                for ($i=1; $i <=5; $i++) {
                    $inputOperator = $i;

                    if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $inputOperator)) {
                        if ($senderidgatewayInfo->output_operator ==1) {
                            // Grameenphon

                            $outputUrls[$inputOperator] = "/internal/process-gp";
                            $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                        } else if ($senderidgatewayInfo->output_operator ==2) {
                            // Banglalink

                            $outputUrls[$inputOperator] = "/internal/process-bl";
                            // $outputUrls[$inputOperator] = "/internal/process-gp";
                            $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                        } else if ($senderidgatewayInfo->output_operator ==4) {
                            //there is no gateway for Airtel. gateway is robi. so output operator can not be 3

                            // Airtel/Robi

                            $outputUrls[$inputOperator] = "/internal/process-robi";
                            // $outputUrls[$inputOperator] = "/internal/process-gp";
                            $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                        } else if ($senderidgatewayInfo->output_operator ==5) {
                            // TeleTalk

                            $outputUrls[$inputOperator] = "/internal/process-ttk";
                            // $outputUrls[$inputOperator] = "/internal/process-gp";
                            $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                        } else if ($senderidgatewayInfo->output_operator ==11) {
                            // TeleTalk

                            $outputUrls[$inputOperator] = "/internal/process-custom";
                            // $outputUrls[$inputOperator] = "/internal/process-gp";
                            $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                        } else if ($senderidgatewayInfo->output_operator ==12) {
                            // metro

                            $outputUrls[$inputOperator] = "/internal/process-custom2";
                            // $outputUrls[$inputOperator] = "/internal/process-gp";
                            $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                        }  else if ($senderidgatewayInfo->output_operator ==13) {
                            // fusion

                            $outputUrls[$inputOperator] = "/internal/process-custom3";
                            // $outputUrls[$inputOperator] = "/internal/process-gp";
                            $senderidGateways[$inputOperator] = $senderidgatewayInfo->id;
                        }
                    }
                }


                $client = new Client(['base_uri' => $this->baseUrl]);

                // Initiate each request but do not block
                $promises = [
                    'gp'    => $client->getAsync($outputUrls[1], [
                        'query' => [
                                "api_token"         => $this->apiToken,
                                "campaign"          => $campaignId,
                                "numberStatus"      => $numberStatus,
                                "senderidGateway"   => $senderidGateways[1]
                            ]
                        ]),

                    'bl'   => $client->getAsync($outputUrls[2], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[2]
                            ]
                        ]),

                    'airtel'   => $client->getAsync($outputUrls[3], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[3]
                            ]
                        ]),
                    'robi'   => $client->getAsync($outputUrls[4], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[4]
                            ]
                        ]),

                    'ttk'   => $client->getAsync($outputUrls[5], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[5]
                            ]
                        ]),
                        
                    'custom'   => $client->getAsync($outputUrls[11], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[11]
                            ]
                        ]),
                    'custom2'   => $client->getAsync($outputUrls[12], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[12]
                            ]
                        ]),
                    'custom3'   => $client->getAsync($outputUrls[13], [
                        'query' => [
                                "api_token"     => $this->apiToken,
                                "campaign"      => $campaignId,
                                "numberStatus"  => $numberStatus,
                                "senderidGateway"   => $senderidGateways[13]
                            ]
                        ]),
                ];

                // Wait for the requests to complete, even if some of them fail
                $responses = Promise\Utils::settle($promises)->wait();

                $gpResponse      = json_decode($responses['gp']['value']->getBody());
                $blResponse      = json_decode($responses['bl']['value']->getBody());
                $airtelResponse  = json_decode($responses['airtel']['value']->getBody());
                $robiResponse    = json_decode($responses['robi']['value']->getBody());
                $ttkResponse     = json_decode($responses['ttk']['value']->getBody());
                $customResponse  = json_decode($responses['custom']['value']->getBody());
                $custom2Response = json_decode($responses['custom2']['value']->getBody());
                $custom3Response = json_decode($responses['custom3']['value']->getBody());
                

                if ($gpResponse!="failed") {
                    $totalSuccessCount += $gpResponse->successCount;
                    $totalFailedCount  += $gpResponse->failedCount;
                }
                if ($blResponse!="failed") {
                    $totalSuccessCount += $blResponse->successCount;
                    $totalFailedCount  += $blResponse->failedCount;
                }
                if ($airtelResponse!="failed") {
                    $totalSuccessCount += $airtelResponse->successCount;
                    $totalFailedCount  += $airtelResponse->failedCount;
                }
                if ($robiResponse!="failed") {
                    $totalSuccessCount += $robiResponse->successCount;
                    $totalFailedCount  += $robiResponse->failedCount;
                }
                if ($ttkResponse!="failed") {
                    $totalSuccessCount += $ttkResponse->successCount;
                    $totalFailedCount  += $ttkResponse->failedCount;
                }
                if ($customResponse!="failed") {
                    $totalSuccessCount += $customResponse->successCount;
                    $totalFailedCount  += $customResponse->failedCount;
                }
                if ($custom2Response!="failed") {
                    $totalSuccessCount += $custom2Response->successCount;
                    $totalFailedCount  += $custom2Response->failedCount;
                }
                if ($custom3Response!="failed") {
                    $totalSuccessCount += $custom3Response->successCount;
                    $totalFailedCount  += $custom3Response->failedCount;
                }

                //end operator processing of campaign


                $campaign->status = 'Complete';
                $campaign->save();

                $category = $campaign->category;
                $delayTime = 300;

                //process refund here
                //get failed numbers in the campaign
                $campaignType = $campaign->campaign_type;
                if ($campaignType=='A') {
                    $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    $delayTime = 300;
                } else if ($campaignType=='B') {
                    $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    $delayTime = 900;
                } else if ($campaignType=='C') {
                    $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    $delayTime = 2400;
                } else if ($campaignType=='D') {
                    $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    $delayTime = 3600;
                } else if ($campaignType=='E') {
                    $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->count();
                    $delayTime = 7200;
                }

                if ($failedSmsCount>0) {
                    //campaign has failed number

                    //get the latest user balance
                    $user = Users::find($campaign->user_id);
                    
                    if ($senderId->type==2) {
                        $senderidType = 'nonmask';
                        $currentBalance = $user->nonmask_balance;
                    } else if ($senderId->type==1) {
                        $senderidType = 'mask';
                        $currentBalance = $user->mask_balance;
                    }
                    $creditFailedCount = $failedSmsCount * $campaign->sms_qty;
                    

                    if ( null !==$request->getParam('failedretry')) {

                        $newBalance = $currentBalance + $creditFailedCount;

                       //update the user's balance
                        UserBalanceManager::updateBalance($user->id, $category, $newBalance);


                        //create the account transaction
                        $transaction = AccountTransaction::create([
                           'type'        => $category,
                           'user'        => $user->id,
                           'txn_type'    => $senderidType.'_cam_retry_refund',
                           'reference'   => $campaign->id,
                           'debit'       => 0,
                           'credit'      => $creditFailedCount,
                           'balance'     => $newBalance,
                           'note'        => $failedSmsCount. ' numbers failed in campaign after retry',
                           'active'      => 1,
                        ]);
                        //end of refund processing

                        
                    } else {
                       //no old refund transaction for this campaign
                       
                        $newBalance = $currentBalance + $creditFailedCount;

                       //update the user's balance
                        UserBalanceManager::updateBalance($user->id, $category, $newBalance);


                        //create the account transaction
                        $transaction = AccountTransaction::create([
                           'type'        => $category,
                           'user'        => $user->id,
                           'txn_type'    => $senderidType.'_cam_refund',
                           'reference'   => $campaign->id,
                           'debit'       => 0,
                           'credit'      => $creditFailedCount,
                           'balance'     => $newBalance,
                           'note'        => $failedSmsCount. ' numbers failed in campaign',
                           'active'      => 1,
                        ]);

                       //new entry created
                    }
                    //refund done

                    $campaign->failed_sms = $creditFailedCount;
                    $campaign->save();
                }


                // asynchronous call for fetching delivery request
                $this->fastRequest($this->baseUrl.'internal/campaign-dl-report?api_token='.$request->getParam('api_token').'&campaignId='.$campaign->id.'&delayTime='.$delayTime);

                return $response->withJson([
                    'status'    => "success",
                    'message'   => "Campaign sms sent successfully",
                    'success'   => $totalSuccessCount,
                    'failed'    => $totalFailedCount,
                ], 200);
            }
        }
    }

    public function processCampaignFailedRetry($request, $response)
    {
        error_reporting(0);

        $campaignId = $request->getParam('campaign');

        $campaign = SmsCampaigns::find($campaignId);

        $campaignSatus = $campaign->status;

        if ($campaign) {
            //check age of the campaign
            $minutes = abs(strtotime($campaign->created_at) - time()) / 60;

            if ($campaign->status == 'Complete' || $minutes>20) {
                // PENDING NUMBERS PROCESSING (FOR UNEXPECTED ERROR)
                // THERE WILL BE NO REFUND AND BALANCE ADJUSTMENT FOR PENDING NUMBERS
                // BECAUSE THESE SMS ARE PENDING DUE TO SYSTEM UNABLE TO CATCH THE OPERATOR ERROR AND NUMBERS ARE LEFT PENDING
                // GET PENDING NUMBERS

                $campaignType = $campaign->campaign_type;

                if ($campaignType=='A') {
                    $pendingSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='B') {
                    $pendingSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='C') {
                    $pendingSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='D') {
                    $pendingSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='E') {
                    $pendingSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 0)->count();
                }

                if ($pendingSmsCount) {
                    $senderId = SenderidMaster::find($campaign->sender_id);
                    ///process operator retry FOR THE PENDING NUMBERS

                    $this->fastRequest($this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token').'&failedretry=0');
                }



                //failed numbers processing
                //get failed numbers in the campaign
                if ($campaignType=='A') {
                    $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='B') {
                    $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='C') {
                    $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='D') {
                    $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='E') {
                    $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->count();
                }

                if ($failedSmsCount>0) {
                    $senderId = SenderidMaster::find($campaign->sender_id);
                    
                    $user = Users::find($campaign->user_id);
                    if ($senderId->type==2) {
                        $currentBalance = $user->nonmask_balance;
                    } else if ($senderId->type==1) {
                        $currentBalance = $user->mask_balance;
                    }

                    $creditFailedCount = $failedSmsCount * $campaign->sms_qty;

                    if($campaignSatus=='Complete'){
                        if ($currentBalance < $creditFailedCount) {
                            $neededBalance = $creditFailedCount - $currentBalance;
                            return $response->withJson([
                                'status'    => "error",
                                'message'   => "Insufficient balance! You need ". $neededBalance. ' more sms credit to try resend.',
                            ], 200);
                        }
                    }
                    
                    ///process operator retry

                    $this->fastRequest($this->baseUrl.'process-campaign-operatorsms/'.$campaign->id.'?api_token='.$request->getParam('api_token').'&failedretry=3');

                    return $response->withJson([
                        'status'    => "success",
                        'message'   => "Campaign failed numbers retry processing started!",
                        'campaign'  => $campaign->id,
                    ], 200);
                }

                return $response->withJson([
                    'status'    => "error",
                    'message'   => "No failed numbers to retry!",
                ], 200);
            } else {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => "Campaign is not completed yet! Please wait for the campaign to be completed and then try again.",
                ], 200);
            }
        }
        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request",
        ], 200);
    }


    public function processForceRefund($request, $response)
    {
        error_reporting(0);

        $campaignId = $request->getParam('campaign');

        $campaign = SmsCampaigns::find($campaignId);
        $category = $campaign->category;
        




        if ($campaign) {
            //check age of the campaign
            $minutes = abs(strtotime($campaign->created_at) - time()) / 60;

            if ($campaign->status == 'Pending' && $minutes>20) {
                // PENDING NUMBERS PROCESSING (FOR UNEXPECTED ERROR)
                // THERE WILL BE NO REFUND AND BALANCE ADJUSTMENT FOR PENDING NUMBERS
                // BECAUSE THESE SMS ARE PENDING DUE TO SYSTEM UNABLE TO CATCH THE OPERATOR ERROR AND NUMBERS ARE LEFT PENDING
                // GET PENDING NUMBERS

                $campaignType = $campaign->campaign_type;

                if ($campaignType=='A') {
                    $pendingSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='B') {
                    $pendingSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='C') {
                    $pendingSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='D') {
                    $pendingSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 0)->count();
                } else if ($campaignType=='E') {
                    $pendingSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 0)->count();
                }

                if ($pendingSmsCount) {
                    
                }



                //failed numbers processing
                //get failed numbers in the campaign
                if ($campaignType=='A') {
                    $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='B') {
                    $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='C') {
                    $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='D') {
                    $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->count();
                } else if ($campaignType=='E') {
                    $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->count();
                }

                $totalFailedCount = $failedSmsCount+$pendingSmsCount;

                if ($totalFailedCount>0) {

                    //campaign has failed number

                    $senderId = SenderidMaster::find($campaign->sender_id);

                    $user = Users::find($campaign->user_id);
                    if ($senderId->type==2) {
                        $senderidType = 'nonmask';
                        $currentBalance = $user->nonmask_balance;
                    } else if ($senderId->type==1) {
                        $senderidType = 'mask';
                        $currentBalance = $user->mask_balance;
                    }
                    $creditFailedCount = $totalFailedCount * $campaign->sms_qty;


                    //no old refund transaction for this campaign
                    
                    $newBalance = $currentBalance + $creditFailedCount;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);


                    //create the account transaction
                    $transaction = AccountTransaction::create([
                        'type'        => $category,
                        'user'        => $user->id,
                        'txn_type'    => $senderidType.'_cam_refund',
                        'reference'   => $campaign->id,
                        'debit'       => 0,
                        'credit'      => $creditFailedCount,
                        'balance'     => $newBalance,
                        'note'        => $failedSmsCount. ' numbers failed or left pending in campaign',
                        'active'      => 1,
                    ]);

                    //new entry created

                    $campaign->status = 'Complete';
                    $campaign->failed_sms = $creditFailedCount;
                    $campaign->save();
                    //refund done


                

                    return $response->withJson([
                        'status'    => "success",
                        'message'   => "Failed and pending numbers of the campaign refunded successfully!",
                        'campaign'  => $campaign->id,
                    ], 200);
                }

                return $response->withJson([
                    'status'    => "error",
                    'message'   => "No failed or pending numbers to refund!",
                ], 200);
            } else {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => "Campaign is not completed yet! Please wait for the campaign to be completed and then try again.",
                ], 200);
            }
        }
        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request",
        ], 200);
    }


    public function processAsyncGP($request, $response)
    {
        $campaignId = $request->getParam('campaign');
        $numberStatus = $request->getParam('numberStatus');

        $campaign = SmsCampaigns::find($campaignId);

        if ($campaign) {
            $senderidGateway = $request->getParam('senderidGateway');
            $senderidgatewayInfo = SenderidGateways::find($senderidGateway);

            $operatorResponse = GP::sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus);
            // dd($operatorResponse);

            if ($operatorResponse) {
                $successCount = $operatorResponse['successCount'];
                $failedCount = $operatorResponse['failedCount'];

                return $response->withJson([
                    'successCount'    => 2,
                    'failedCount'     => 0,
                ], 200);
            } else {
                return "failed";
            }
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request!",
        ], 400);
    }

    public function processAsyncBL($request, $response)
    {
        $campaignId = $request->getParam('campaign');
        $numberStatus = $request->getParam('numberStatus');

        $campaign = SmsCampaigns::find($campaignId);

        if ($campaign) {
            $senderidGateway = $request->getParam('senderidGateway');
            $senderidgatewayInfo = SenderidGateways::find($senderidGateway);

            $operatorResponse = Banglalink::sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus);

            if ($operatorResponse) {
                $successCount = $operatorResponse['successCount'];
                $failedCount = $operatorResponse['failedCount'];

                return $response->withJson([
                    'successCount'    => $successCount,
                    'failedCount'     => $failedCount,
                ], 200);
            } else {
                return "failed";
            }
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request!",
        ], 400);
    }


    public function processAsyncRobi($request, $response)
    {
        $campaignId = $request->getParam('campaign');
        $numberStatus = $request->getParam('numberStatus');

        $campaign = SmsCampaigns::find($campaignId);

        if ($campaign) {
            $senderidGateway = $request->getParam('senderidGateway');
            $senderidgatewayInfo = SenderidGateways::find($senderidGateway);

            $operatorResponse = Robi::sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus);

            if ($operatorResponse) {
                $successCount = $operatorResponse['successCount'];
                $failedCount = $operatorResponse['failedCount'];

                return $response->withJson([
                    'successCount'    => $successCount,
                    'failedCount'     => $failedCount,
                ], 200);
                // return "done";
            } else {
                return "failed";
            }
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request!",
        ], 400);
    }

    public function processAsyncTTk($request, $response)
    {

        $campaignId = $request->getParam('campaign');
        $numberStatus = $request->getParam('numberStatus');

        $campaign = SmsCampaigns::find($campaignId);

        if ($campaign) {
            $senderidGateway = $request->getParam('senderidGateway');
            $senderidgatewayInfo = SenderidGateways::find($senderidGateway);

            $operatorResponse = Teletalk::sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus);

            if ($operatorResponse) {
                $successCount = $operatorResponse['successCount'];
                $failedCount = $operatorResponse['failedCount'];

                return $response->withJson([
                    'successCount'    => $successCount,
                    'failedCount'     => $failedCount,
                ], 200);
            } else {
                return "failed";
            }
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request!",
        ], 400);
    }

    public function processAsyncCustom($request, $response)
    {

        $campaignId = $request->getParam('campaign');
        $numberStatus = $request->getParam('numberStatus');

        $campaign = SmsCampaigns::find($campaignId);

        if ($campaign) {
            $senderidGateway = $request->getParam('senderidGateway');
            $senderidgatewayInfo = SenderidGateways::find($senderidGateway);

            $operatorResponse = CustomOperator::sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus);

            if ($operatorResponse) {
                $successCount = $operatorResponse['successCount'];
                $failedCount = $operatorResponse['failedCount'];

                return $response->withJson([
                    'successCount'    => $successCount,
                    'failedCount'     => $failedCount,
                ], 200);
            } else {
                return "failed";
            }
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request!",
        ], 400);
    }

    public function processAsyncCustom2($request, $response)
    {

        $campaignId = $request->getParam('campaign');
        $numberStatus = $request->getParam('numberStatus');

        $campaign = SmsCampaigns::find($campaignId);

        if ($campaign) {
            $senderidGateway = $request->getParam('senderidGateway');
            $senderidgatewayInfo = SenderidGateways::find($senderidGateway);

            $operatorResponse = CustomOperator2::sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus);
            // var_dump($operatorResponse);exit;

            if ($operatorResponse) {
                $successCount = $operatorResponse['successCount'];
                $failedCount = $operatorResponse['failedCount'];

                return $response->withJson([
                    'successCount'    => $successCount,
                    'failedCount'     => $failedCount,
                ], 200);
            } else {
                return "failed";
            }
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request!",
        ], 400);
    }

    public function processAsyncCustom3($request, $response)
    {

        $campaignId = $request->getParam('campaign');
        $numberStatus = $request->getParam('numberStatus');

        $campaign = SmsCampaigns::find($campaignId);

        if ($campaign) {
            $senderidGateway = $request->getParam('senderidGateway');
            $senderidgatewayInfo = SenderidGateways::find($senderidGateway);

            $operatorResponse = CustomOperator3::sendMultiMessage($campaign, $senderidgatewayInfo, $numberStatus);
            var_dump($operatorResponse);exit;

            if ($operatorResponse) {
                $successCount = $operatorResponse['successCount'];
                $failedCount = $operatorResponse['failedCount'];

                return $response->withJson([
                    'successCount'    => $successCount,
                    'failedCount'     => $failedCount,
                ], 200);
            } else {
                return "failed";
            }
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => "Invalid Request!",
        ], 400);
    }

    public function campaignLiveStatus($request, $response)
    {
        $campaignId = $request->getParam('campaign');

        $campaign = SmsCampaigns::find($campaignId);

        if (!$campaign) {
            return $response->withJson([
                'status'    => "error",
                'message'   => "Invalid Request",
            ], 200);
        }

        $campaignType = $campaign->campaign_type;

        if ($campaignType=='A') {
            $processed = SmsCampaignNumbersA::where('campaign_id', $campaignId)->whereIn('status', [1,2,5])->count();
            $pending = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('status', 0)->count();
            $failed = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('status', 3)->count();

            //operator wise
            // gp - 1
            $gpSent = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 1)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $gpFailed = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 1)->where('status', 3)->count();

            //bl - 2
            $blSent = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 2)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $blFailed = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 2)->where('status', 3)->count();

            //airtel - 3
            $airtelSent = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 3)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $airtelFailed = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 3)->where('status', 3)->count();

            //robi - 4
            $robiSent = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 4)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $robiFailed = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 4)->where('status', 3)->count();

            //ttk - 5
            $ttkSent = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 5)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $ttkFailed = SmsCampaignNumbersA::where('campaign_id', $campaignId)->where('operator', 5)->where('status', 3)->count();
        } else if ($campaignType=='B') {
            $processed = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $pending = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('status', 0)->count();
            $failed = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('status', 3)->count();

            //operator wise
            // gp - 1
            $gpSent = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 1)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $gpFailed = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 1)->where('status', 3)->count();

            //bl - 2
            $blSent = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 2)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $blFailed = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 2)->where('status', 3)->count();

            //airtel - 3
            $airtelSent = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 3)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $airtelFailed = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 3)->where('status', 3)->count();

            //robi - 4
            $robiSent = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 4)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $robiFailed = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 4)->where('status', 3)->count();

            //ttk - 5
            $ttkSent = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 5)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $ttkFailed = SmsCampaignNumbersB::where('campaign_id', $campaignId)->where('operator', 5)->where('status', 3)->count();
        } else if ($campaignType=='C') {
            $processed = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $pending = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('status', 0)->count();
            $failed = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('status', 3)->count();

            //operator wise
            // gp - 1
            $gpSent = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 1)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $gpFailed = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 1)->where('status', 3)->count();

            //bl - 2
            $blSent = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 2)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $blFailed = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 2)->where('status', 3)->count();

            //airtel - 3
            $airtelSent = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 3)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $airtelFailed = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 3)->where('status', 3)->count();

            //robi - 4
            $robiSent = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 4)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $robiFailed = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 4)->where('status', 3)->count();

            //ttk - 5
            $ttkSent = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 5)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $ttkFailed = SmsCampaignNumbersC::where('campaign_id', $campaignId)->where('operator', 5)->where('status', 3)->count();
        } else if ($campaignType=='D') {
            $processed = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $pending = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('status', 0)->count();
            $failed = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('status', 3)->count();

            //operator wise
            // gp - 1
            $gpSent = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 1)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $gpFailed = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 1)->where('status', 3)->count();

            //bl - 2
            $blSent = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 2)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $blFailed = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 2)->where('status', 3)->count();

            //airtel - 3
            $airtelSent = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 3)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $airtelFailed = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 3)->where('status', 3)->count();

            //robi - 4
            $robiSent = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 4)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $robiFailed = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 4)->where('status', 3)->count();

            //ttk - 5
            $ttkSent = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 5)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $ttkFailed = SmsCampaignNumbersD::where('campaign_id', $campaignId)->where('operator', 5)->where('status', 3)->count();
        } else if ($campaignType=='E') {
            $processed = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $pending = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('status', 0)->count();
            $failed = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('status', 3)->count();

            //operator wise
            // gp - 1
            $gpSent = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 1)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $gpFailed = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 1)->where('status', 3)->count();

            //bl - 2
            $blSent = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 2)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $blFailed = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 2)->where('status', 3)->count();

            //airtel - 3
            $airtelSent = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 3)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $airtelFailed = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 3)->where('status', 3)->count();

            //robi - 4
            $robiSent = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 4)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $robiFailed = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 4)->where('status', 3)->count();

            //ttk - 5
            $ttkSent = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 5)->where('status', '!=', 0)->where('status', '!=', 3)->count();
            $ttkFailed = SmsCampaignNumbersE::where('campaign_id', $campaignId)->where('operator', 5)->where('status', 3)->count();
        }

        

        return $response->withJson([
            'status'            => "success",
            'processed'         => $processed,
            'pending'           => $pending,
            'failed'            => $failed,
            'campaign_status'   => $campaign->status,
            'gpSent'            => $gpSent,
            'gpFailed'          => $gpFailed,
            'blSent'            => $blSent,
            'blFailed'          => $blFailed,
            'airtelSent'        => $airtelSent,
            'airtelFailed'      => $airtelFailed,
            'robiSent'          => $robiSent,
            'robiFailed'        => $robiFailed,
            'ttkSent'           => $ttkSent,
            'ttkFailed'         => $ttkFailed,
        ], 200);
    }

    public function userBalance($request, $response)
    {

        $api_token = $request->getParam('api_token');
        
        //get the user
        $user = $this->auth->user($api_token);

        if (!$user) {
            return $response->withJson([
                'status'    => "error",
                'message'   => "Invalid Request",
            ], 200);
        }

        return $response->withJson([
            'status'    => "success",
            'mask'      => $user->mask_balance,
            'nonmask'   => $user->nonmask_balance,
            'voice'     => $user->voice_balance,
        ], 200);
    }


    public function uploadContacts($request, $response)
    {
        error_reporting(0);

        if (!$request->getParam('contactgroup') ||
            !$request->getParam('api_token')
        ) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Required field is empty',
            ], 200);
        }

        $api_token = $request->getParam('api_token');
        
        
        //get the user
        $user = $this->auth->user($api_token);

        if ($user->status == 'n') {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'We have found unethical transection from your account, your account is blocked until the issue is solve. Please contact support team.',
            ], 200);
        }

        $userId = $user->id;

        $contacts = [];
        $contactGroup = $request->getParam('contactgroup');
        
        $contacts = $this->validMobileFromFile($userId, $request);

        if (!$contacts) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'There is an error, problem may be invalid file format!',
            ], 200);
        }

        if (isset($contacts)) {
            //sort the array in ascending order
            sort($contacts);
            $contacts = array_unique($contacts);
        }

        //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

        $this->totalValidContact = count($contacts);

        $contactGroupId = $request->getParam('contactgroup');
        $countContacts = count($contacts);
        if ($countContacts>0) {
            foreach ($contacts as $contact) {
                Contact::create([
                    'user_id'               => $user->id,
                    'contact_group_id'      => $contactGroupId,
                    'contact_name'          => '',
                    'contact_number'        => $contact,
                    'status'                => 1
                ]);
            }
        }

        return $response->withJson([
                'status'    => "success",
                'msg'   => $countContacts.' Contacts from file inserted successfully',
            ], 200);
    }


    public function storeLcSms($request, $response)
    {
        // error_reporting(0);

        if (!$request->getParam('message') ||
            !$request->getParam('api_token')
        ) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Required field is empty, message content & senderid are required parameter',
            ], 200);
        }

        $api_token = $request->getParam('api_token');

        $sentThrough = 1;
        if ($request->getParam('source')) {
            $sentThrough = 0;
        }
        
        
        //get the user
        $user = $this->auth->user($api_token);

        if ($user->status == 'n') {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'We have found unethical transection from your account, your account is blocked until the issue is solve. Please contact support team.',
            ], 200);
        }

        $userId = $user->id;


        $message = $request->getParam('message');

        $contacts = [];

        $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

        $this->messagetype = SmsSystm::getMessageType($request->getparam('message'));

        if ($this->messagetype=='unicode') {
                $is_unicode = 1;
        } else {
            $is_unicode = 0;
        }

        $root_userid = $user->root_user_id;

        $reseller_id = $user->reseller_id;

        $category = 4;

        $senderidType = 'lc';
        $currentBalance = $user->lowcost_balance;
        
        $totalSms = 0;

        if ($request->getParam('numbertype')) {
            $numberType = $request->getParam('numbertype');
        } else {
            $numberType = 'single';
        }

        

        //Single contact number start
        if ($numberType == 'single') {
            if (!$request->getParam('contact_number')) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'contact_number is required parameter',
                ], 200);
            }

            $contactlist = is_array($request->getParam('contact_number')) ? $request->contact_number : explode(",", str_replace("\n", ",", str_replace(" ", ",", $request->getParam('contact_number'))));

            //get valid contacts from this array of numbers
            $contacts = array();
            foreach ($contactlist as $mobileNo) {
                if ($mobileNo = ContactsAndGroups::mobileNumber($mobileNo)) {
                    array_push($contacts, $mobileNo);
                }
            }

            if (isset($contacts[0])) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
            } else {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Invalid format of contact numbers. Please check your cotact numbers',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            $totalSms = $this->messagecount*$this->totalValidContact;


            if ($currentBalance < $totalSms) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient low cost sms balance',
                ], 200);
            }
        } else {
            $contactGroup = explode(',', $request->getParam('contactgroup'));

            
            $contacts = $numberType == 'contgroup' ? $this->getValidNumbers($userId, $contactGroup) : $this->validMobileFromFile($userId, $request);

            if (isset($contacts)) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
            }
            
            if (!$contacts) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'There is an error, problem may be invalid file format!',
                ], 200);
            }

            

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            $totalSms = ($this->totalValidContact*$this->messagecount);

            if ($currentBalance < $totalSms) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient low cost sms balance',
                ], 200);
            }
        }

        $newBalance = $currentBalance - $totalSms;

        //deduce the user's balance
        UserBalanceManager::updateBalance($user->id, $category, $newBalance);


        // print_r($contacts); die();

        //here is the array of numbers in integer formatted

        if ($this->totalValidContact>0) {
            $campaignId = date('y') .$user->id. date('mdhis'). mt_rand(100, 999);

            $campaignName = "";
            if ($request->getParam('campaign_name')) {
                $campaignName = $request->getParam('campaign_name');
            } else {
                $campaignName = "Campaign ". $campaignId;
            }

            $campaignStatus = 'Pending';
            


            $campaign = SmsLowcostCampaigns::create([
                'campaign_no'           => $campaignId,
                'campaign_name'         => $campaignName,
                'campaign_description'  => $request->getParam('campaign_description'),
                'user_id'               => $user->id,
                'is_unicode'            => $is_unicode,
                'content'               => $message,
                'sms_qty'               => $this->messagecount,
                'total_numbers'         => $this->totalValidContact,
                'status'                => $campaignStatus,
                'active'                => 1,
            ]);

            //create the account transaction
            $transaction = AccountTransaction::create([
                'type'        => $category,
                'user'        => $user->id,
                'txn_type'    => $senderidType.'_campaign',
                'reference'   => $campaign->id,
                'debit'       => $totalSms,
                'credit'      => 0,
                'balance'     => $newBalance,
                'note'        => 'Campaign created',
                'active'      => 1,
            ]);


            foreach ($contacts as $key => $mobile) {
                //insert the number to campaign-numbers
                SmsLowcost::create([
                    'campaign_id'    => $campaign->id,
                    'user_id'        => $user->id,
                    'number'         => $mobile,
                    'content'        => $message,
                    'is_unicode'     => $is_unicode,
                    'qty'            => $this->messagecount,
                    'status'         => 0,//pending
                    'active'         => 1,
                ]);
            }
                
            //insertion complete

            //set operator by prefix

                
            $campaignNumbersTable = 'sms_lowcost';
            $db_conx = $this->DbConnect->connect();
            
            //gp
            $sql = "UPDATE $campaignNumbersTable SET `operator`=1 WHERE campaign_id=$campaign->id AND number LIKE '17%' OR number LIKE '13%'";
            $query = mysqli_query($db_conx, $sql);

            //bl
            $sql = "UPDATE $campaignNumbersTable SET `operator`=2 WHERE campaign_id=$campaign->id AND number LIKE '19%' OR number LIKE '14%'";
            $query = mysqli_query($db_conx, $sql);

            //airtel
            $sql = "UPDATE $campaignNumbersTable SET `operator`=3 WHERE campaign_id=$campaign->id AND number LIKE '16%'";
            $query = mysqli_query($db_conx, $sql);

            //robi
            $sql = "UPDATE $campaignNumbersTable SET `operator`=4 WHERE campaign_id=$campaign->id AND number LIKE '18%'";
            $query = mysqli_query($db_conx, $sql);

            //telealk
            $sql = "UPDATE $campaignNumbersTable SET `operator`=5 WHERE campaign_id=$campaign->id AND number LIKE '15%'";
            $query = mysqli_query($db_conx, $sql);
            //operator processing complete




            $campaign->status = 'Initiated';
            $campaign->save();


            return $response->withJson([
                'status'    => "success",
                'message'   => "SMS processing initiated successfully.",
                'campaign'    => $campaign->id,
            ], 200);
        } else {
            //not a campaign or single number request

            $mobile = $contacts[0];

            $msgStatus = 'Pending';
            if ($isScheduled==1) {
                $msgStatus = 'Scheduled';
            }

            // check if already a transaction is created or not
            $accTransaction = AccountTransaction::where('user', $user->id)->where('type', $category)->where('txn_type', $senderidType.'_single')->where('credit', 0)->whereDate('created_at', '=', date('Y-m-d'))->first();

            if ($accTransaction) {
                // increase the transaction units amount by number of sms
                $accTransaction->debit = $accTransaction->debit+$totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();
            } else {
                 //no transaction of the user for the day

                //create the account transaction
                $accTransaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_single',
                    'debit'       => $totalSms,
                    'credit'      => 0,
                    'balance'     => $newBalance,
                    'note'        => 'Individual sms sent through web', //web or api
                    'active'      => 1,
                ]);
            }

            //generate message Id
            $lastMsgId = null;

            $lastMsg = SmsSingle::orderBy('id', 'desc')->first();
            if ($lastMsg) {
                $lastMsgId = $lastMsg->id;
                $lastMsgId++;
            } else {
                $lastMsgId = 1;
            }
            $smsId = date('ymd') .$user->id. $lastMsgId. mt_rand(10000, 99099);

            $operatorId = null;

            //check if MNP Dipping is enabled
            if ($user->live_dipping == true) {
                //process number's operator checking thow MNP Dipping API

                // check each numbers through MNP dipping
                    
                if ($optId = SmsSystm::checkMnpOperator($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }


            //mnp dipping ends
            } else {
                //if mnp dipping is disabled

                // check operator by prefix
                if ($optId = SmsSystm::getOperatorByPrefix($mobile)) {
                    $operatorId = $optId;
                } else {
                    //credit the wrong numbers smscount

                    $creditWrongNumbers = $this->messagecount;
                    $newBalance = $newBalance + $creditWrongNumbers;

                    //update the user's balance
                    UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                    //decrease account transaction value
                    // increase the transaction units amount by number of sms
                    $accTransaction->debit = $accTransaction->debit - $totalSms;
                    $accTransaction->balance = $accTransaction->balance - $totalSms;
                    $accTransaction->save();

                    
                    return $response->withJson([
                        'status'    => "error",
                        'message'   => "Invalid mobile number",
                    ], 200);
                }
                

            //non dipping end
            }

            
            // insert the number to individual-numbers
            $smsSingle = SmsSingle::create([
                'sms_id'            => $smsId,
                'user_id'           => $user->id,
                'sender_id'         => $senderId->id,
                'category'          => $category,
                'number'            => $mobile,
                'operator'          => $operatorId,
                'is_unicode'        => $is_unicode,
                'content'           => $message,
                'qty'               => $this->messagecount,
                'sent_through'      => $sentThrough, //0=web, 1=api
                'is_scheduled'      => $isScheduled,
                'scheduled_time'    => $scheduledTime,
                'status'            => 0,
                'active'            => 1,
            ]);
            // insertion complete


            // process operator wise processing
            if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $operatorId)) {
                if ($senderidgatewayInfo->output_operator ==1) {
                    // Grameenphon
                    $operatorResponse = GP::sendSingleMessage($smsSingle, $senderidgatewayInfo);
                } else if ($senderidgatewayInfo->output_operator ==2) {
                    // Banglalink
                    $operatorResponse = Banglalink::sendSingleMessage($smsSingle, $senderidgatewayInfo);
                } else if ($senderidgatewayInfo->output_operator ==3 || $senderidgatewayInfo->output_operator ==4) {
                    // Airtel/Robi
                    $operatorResponse = Robi::sendSingleMessage($smsSingle, $senderidgatewayInfo);
                } else if ($senderidgatewayInfo->output_operator ==5) {
                    // TeleTalk
                    $operatorResponse = Teletalk::sendSingleMessage($smsSingle, $senderidgatewayInfo);
                }
            }

            if ($operatorResponse=='success') {
                return $response->withJson([
                    'status'    => "success",
                    'message'   => "SMS sent successfully",
                    'smsid'     => $smsId,
                    'SmsCount'  => $smsSingle->qty,
                ], 200);
            } else {
                //process refund here
                $newBalance = $currentBalance + $totalSms;

                //deduce the user's balance
                UserBalanceManager::updateBalance($user->id, $category, $newBalance);

                //refund to transaction
                $accTransaction->debit = $accTransaction->debit + $totalSms;
                $accTransaction->balance = $accTransaction->balance + $totalSms;
                $accTransaction->save();

                //refund done

                
                return $response->withJson([
                        'status'    => "error",
                        'message'   => $operatorResponse,
                    ], 200);
            }

            //System Error
            //process refund here
            $newBalance = $currentBalance + $totalSms;

            //deduce the user's balance
            UserBalanceManager::updateBalance($user->id, $category, $newBalance);

            //refund to transaction
            $accTransaction->debit = $accTransaction->debit + $totalSms;
            $accTransaction->balance = $accTransaction->balance + $totalSms;
            $accTransaction->save();

            //refund done


            return $response->withJson([
                'status'    => "error",
                'message'   => 'System Error',
            ], 200);
        }

        //end of individual

        return $response->withJson([
            'status'    => "error",
            'message'   => 'System Error',
        ], 200);
    }

    public function sendDefaultSyatemSMS($request, $response)
    {
        error_reporting(0);

        if (!$request->getParam('message') ||
            !$request->getParam('senderid')
        ) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Required field is empty, message content & senderid are required parameter',
            ], 200);
        }


        $sentThrough = 1;
        
        $userId = 0;


        $message = $request->getParam('message');

        $isScheduled = 0;
        $senderIdName = $request->getParam('senderid');

        //get master senderId
        $senderId = SenderIdDetails::getSenderIdByNameIfValid($userId, $senderIdName);

        if (!$senderId) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'Invalid Sender ID.',
            ], 200);
        }

        $contacts = [];

        $this->messagecount = SmsSystm::getMessageCount($request->getparam('message'));

        if (!$this->messagecount) {
            return $response->withJson([
                'status'    => "error",
                'message'   => 'SMS length exceeded max limit of 8 sms.',
            ], 200);
        }

        $this->messagetype = SmsSystm::getMessageType($request->getparam('message'));

        if ($this->messagetype=='unicode') {
                $is_unicode = 1;
        } else {
            $is_unicode = 0;
        }

        

        

        $currentBalance = 0;

        $category = $senderId->type;

        if ($senderId->type==2) {
            $senderidType = 'nomask';
        } else if ($senderId->type==1) {
            $senderidType = 'mask';
        }

        $totalSms = 0;

        $numberType = 'single';

        

        //Single contact number start
        if ($numberType == 'single') {
            if (!$request->getParam('contact_number')) {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'contact_number is required parameter',
                ], 200);
            }

            $contactlist = is_array($request->getParam('contact_number')) ? $request->contact_number : explode(",", str_replace("\n", ",", str_replace(" ", ",", $request->getParam('contact_number'))));

            //get valid contacts from this array of numbers
            $contacts = array();
            foreach ($contactlist as $mobileNo) {
                if ($mobileNo = ContactsAndGroups::mobileNumber($mobileNo)) {
                    array_push($contacts, $mobileNo);
                }
            }

            if (isset($contacts[0])) {
                //sort the array in ascending order
                sort($contacts);
                $contacts = array_unique($contacts);
            } else {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Invalid format of contact numbers. Please check your cotact numbers',
                ], 200);
            }

            //we are getting a fully sorted and valid set of numbers in $contacts array. this is a fully formatted array of numbers.

            $this->totalValidContact = count($contacts);

            $totalSms = $this->messagecount*$this->totalValidContact;

            if ($totalmaskbal < $totalSms && $senderidType == 'mask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient mask sms balance',
                ], 200);
            }

            if ($totalnonmaskbal < $totalSms && $senderidType == 'nomask') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient nonmask sms balance',
                ], 200);
            }

            if ($totalvoicebal < $totalSms && $senderidType == 'voice') {
                return $response->withJson([
                    'status'    => "error",
                    'message'   => 'Insufficient voice sms balance',
                ], 200);
            }
        }

        $newBalance = 0;


        //here is the array of numbers in integer formatted

        /*
        * if total numbers are within 5 and no campaign request then we will take the requested numbers as individual numbers
        * but if the total numbers exceeds 5 and there is no campaign request then we will convert it to a campaign
        * if there is any campaign request then we will take the equested numbers as campaign
        */
    
        //not a campaign or single number request

        $mobile = $contacts[0];

        $msgStatus = 'Pending';

        //generate message Id
        $lastMsgId = null;

        $lastMsg = SmsSingle::orderBy('id', 'desc')->first();
        if ($lastMsg) {
            $lastMsgId = $lastMsg->id;
            $lastMsgId++;
        } else {
            $lastMsgId = 1;
        }
        $smsId = date('ymd') .$user->id. $lastMsgId. mt_rand(10000, 99099);

        $operatorId = null;


        //mnp dipping is disabled

        // check operator by prefix
        if ($optId = SmsSystm::getOperatorByPrefix($mobile)) {
            $operatorId = $optId;
        }
        

    //non dipping end
    

        
        // insert the number to individual-numbers
        $smsSingle = SmsSingle::create([
            'sms_id'            => $smsId,
            'user_id'           => 0,
            'sender_id'         => $senderId->id,
            'category'          => $category,
            'number'            => $mobile,
            'operator'          => $operatorId,
            'is_unicode'        => $is_unicode,
            'content'           => $message,
            'qty'               => $this->messagecount,
            'sent_through'      => $sentThrough, //0=web, 1=api
            'is_scheduled'      => $isScheduled,
            'scheduled_time'    => $scheduledTime,
            'status'            => 0,
            'active'            => 1,
        ]);
        // insertion complete

        // process operator wise processing
        if ($senderidgatewayInfo = SenderIdDetails::getOutputOperatorGatewayByInput($senderId->id, $operatorId)) {
            if ($senderidgatewayInfo->output_operator ==1) {
                // Grameenphon
                $operatorResponse = GP::sendSingleMessage($smsSingle, $senderidgatewayInfo);
            } else if ($senderidgatewayInfo->output_operator ==2) {
                // Banglalink
                $operatorResponse = Banglalink::sendSingleMessage($smsSingle, $senderidgatewayInfo);
            } else if ($senderidgatewayInfo->output_operator ==3 || $senderidgatewayInfo->output_operator ==4) {
                // Airtel/Robi
                $operatorResponse = Robi::sendSingleMessage($smsSingle, $senderidgatewayInfo);
            } else if ($senderidgatewayInfo->output_operator ==5) {
                // TeleTalk
                $operatorResponse = Teletalk::sendSingleMessage($smsSingle, $senderidgatewayInfo);
            } else if ($senderidgatewayInfo->output_operator == 11) {
                // custom operator
                $operatorResponse = CustomOperator::sendSingleMessage($smsSingle, $senderidgatewayInfo);
            } else if ($senderidgatewayInfo->output_operator == 12) {
                // custom operator
                $operatorResponse = CustomOperator2::sendSingleMessage($smsSingle, $senderidgatewayInfo);
            } else if ($senderidgatewayInfo->output_operator == 13) {
                // custom operator
                $operatorResponse = CustomOperator3::sendSingleMessage($smsSingle, $senderidgatewayInfo);
            }
        }

        if ($operatorResponse=='success') {
            return $response->withJson([
                'status'    => "success",
                'message'   => "SMS sent successfully",
                'smsid'     => $smsId,
                'SmsCount'  => $smsSingle->qty,
            ], 200);
        } else {
            
            return $response->withJson([
                    'status'    => "error",
                    'message'   => $operatorResponse,
                ], 200);
        }

        return $response->withJson([
            'status'    => "error",
            'message'   => 'System Error',
        ], 200);
    
        //end of individual

        return $response->withJson([
            'status'    => "error",
            'message'   => 'System Error',
        ], 200);
    }
}
