<?php

namespace App\Helper;

use App\Models\Users;
use App\Models\SmsSender;
use App\Models\UserSender;
use Illuminate\Support\Str;
use App\Helper\ContactsAndGroups;

use App\Models\ContactGroup;


class SmsSystem
{

	protected $contactgroup;

    protected $client;

    protected $userid;

    protected $groupid;

    protected $totalmsg;

    protected $senderid;

    protected $filecontacts = [];



    public function __construct(
        ContactsAndGroups $contactgroup
        // ,
        // ClientInterface $client,
        // SenderId $senderid
    )
    {
        $this->contactgroup = $contactgroup;

        $this->client = $client;

        $this->senderid = $senderid;
        
    }

	
/**
     * Determine sms message is unicode content | normal text content
     *
     * @param string $message
     * @return void
     */
    public static function getMessageType($message)
    {
        if (strlen($message) != strlen(utf8_decode($message)))
        {
            return 'unicode';
        }

        return 'text';

    }


    /**
     * Manage sms message length in runtime
     *
     * @return void
     */
    public static function getMessageCount($message)
    {
        $countmsg = Str::length($message); //strlen($message);

        $type = SmsSystem::getMessageType($message);

        if($type=='text'){
           
            if ($countmsg <= 160) { 

                return $totalmsg = 1;  

            //} else if ($countmsg <= 310) { 
            } else if ($countmsg > 160 && $countmsg <= 306) { 
                
                return $totalmsg = 2;

            } else if ($countmsg <= 306) { 
                
                return $totalmsg = 2;

            } else if ($countmsg <= 460) {

                return $totalmsg = 3; 

            } else if ($countmsg <= 610) { 
                
                return $totalmsg = 4;

            } else if ($countmsg <= 760) { 
                
                return $totalmsg = 5;

            } else if ($countmsg <= 910) { 
                
                return $totalmsg = 6;

            } else { 
                
                return false;

            } 
        } else {

            $countmsg= Str::length($message, 'UTF-8');//mb_strlen( $message,'UTF-8'); 

            if ($countmsg <= 70) {

                return $totalmsg = 1;  

            } else if ($countmsg > 70 && $countmsg <= 134) {

                return $totalmsg = 2;

            } else if ($countmsg <= 134) {

                return $totalmsg = 2;

            } else if ($countmsg <= 201) {

                return $totalmsg = 3; 

            } else if ($countmsg <= 268) {

                return $totalmsg = 4;

            } else if ($countmsg <= 335) {

                return $totalmsg = 5;

            } else if ($countmsg <= 402) {

                return $totalmsg = 6;

            } else if ($countmsg <= 469) {

                return $totalmsg = 7;

            } else if ($countmsg <= 536) {

                return $totalmsg = 8;

            } else {

                return false;

            } 

        }
    }


    /**
     * Valid mobile number
     *
     * @param int $userid
     * @param int $groupid
     * @return void
     */
    public static function getContactsInGroup($userid, $groupid)
    {
        $mobileNumbers = array();

        $groupinfo = ContactGroup::where('user_id', $userid)
                                    ->where('id',$groupid)
                                    ->where('status',1)
                                    ->get();

        foreach($groupinfo as $group)
        {
            foreach($group->contacts as $contact)
            {
                
                //get integer format of number: 01723 -> 1723                    
                if($mobileNo = ContactsAndGroups::mobileNumber($contact->contact_number)){  
                    array_push($mobileNumbers, $mobileNo);
                }
            }

        }

        if (isset($mobileNumbers)){
            //sort the array in ascending order
            sort($mobileNumbers);
            return $mobileNumbers;
        }

        return false;

    }

    public static function checkMnpOperator($mobile)
    {
        $client = new Client([
            'verify' => false
        ]);

        $clientresponse = $client->request('GET','http://api.lexiconbd.net/lexiconbdmnp.aspx',[
            'query' => [ 
                'apikey' => 'b0652a5086fbe9c03039c7d341c9e73a12863165',
                'number' => $mobile,
            ]
        ]);

        $responsedata =  json_decode($clientresponse->getBody());
        $rescollect = collect($responsedata);

        $code = $rescollect['data'][0]->code;
        $mobile = $rescollect['data'][0]->mobile;
        $prefix = $rescollect['data'][0]->prefix;
        $operator = $rescollect['data'][0]->operator;
        $status = $rescollect['data'][0]->status;
    

        $operatorId = null;
        if (trim($rescollect['data'][0]->operator) == "Grameenphone") {
            $operatorId = 1;
        }

        if (trim($rescollect['data'][0]->operator) == "Banglalink") {
            $operatorId = 2;
        }

        if (trim($rescollect['data'][0]->operator) == "Airtel") {
            $operatorId = 3;
        }

        if (trim($rescollect['data'][0]->operator) == "Robi") {
            $operatorId = 4;
        }

        if (trim($rescollect['data'][0]->operator) == "TeleTalk") {
            $operatorId = 5;
        }

        if (!$operatorId) {
            return false;
        }

        return $operatorId;
    }


    public static function getOperatorByPrefix($mobile)
    {



        $prefix = substr($mobile, 0, 2);
    

        $operatorId = null;
        if ($prefix == 17 || $prefix == 13) {
            $operatorId = 1;
        } else if ($prefix == 19 || $prefix == 14) {
            $operatorId = 2;
        } else if ($prefix == 16) {
            $operatorId = 3;
        } else if ($prefix == 18) {
            $operatorId = 4;
        } else if ($prefix == 15) {
            $operatorId = 5;
        }

        if (!$operatorId) {
            return false;
        }

        return $operatorId;
    }

    
	
}

