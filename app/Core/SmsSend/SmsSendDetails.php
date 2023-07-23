<?php

namespace App\Core\SmsSend;

use App\CheckRuntimeBalance;
use App\Core\ContactsAndGroups\ContactsAndGroups;
use App\Core\Senderid\SenderId;
use App\Core\SmsSend\SmsSend;
use App\Core\Users\ClientInterface;
use App\Datatables\DataTableClass;
use App\Jobs\DlrPUshJob;
use App\User;
use App\UserBalance;
use App\UserCountSms;
use App\UserSentSms;
use App\UserSentSmsBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class SmsSendDetails implements SmsSend
{
    /**
     * Contact group service
     *
     * @var App\Core\ContactsAndGroups\ContactsAndGroupsDetails
     */
    protected $contactgroup;

    protected $client;

    protected $userid;

    protected $groupid;

    protected $totalmsg;

    protected $senderid;

    protected $filecontacts = [];

    public function __construct(
        ContactsAndGroups $contactgroup,
        ClientInterface $client,
        SenderId $senderid
    )
    {
        $this->contactgroup = $contactgroup;

        $this->client = $client;

        $this->senderid = $senderid;
        
    }


    /**
     * Get senderid type
     *
     * @param array $data
     * @return void
     */
    public function getSenderIdType(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }
        
        $senderidtype =  (!is_numeric($data['senderid']))? 'mask':  'nomask';

        return $senderidtype;
    }


    /**
     * Get total contacts in a contact group
     *
     * @param array $data
     * @return void
     */
    public function getTotalNumberOfContacts(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $groupinfo = $this->contactgroup->getGroupByClientAndId($data['user_id'], $data['groupid']);

        return $groupinfo;
    }

    /**
     * Valid mobile number
     *
     * @param int $userid
     * @param string $contacts
     * @return void
     */
    public function validMobileFromFile($userid, $contactlist)
    {
        $contacts = [];

        $newarr = [];
        
        $validprefix =  ["88017","88014","88013","88015","88018","88016","88019"];

        $this->userid = $userid;
     
        foreach($contactlist as $contact)
        {
            $checkcontact = Str::substr($contact,0,2);
            $checkcontactwithplus = Str::substr($contact,0,3);

            //Covert number in format like 01716xxxxxx if start with 88
            $contact_number_n = '';
            if ($checkcontact == 88 || $checkcontactwithplus == '+88')
            {
                $contact_number_n = $checkcontact == 88 ? Str::substr($contact,2,13) : Str::substr($contact,3,13); 
            } else {
                $contact_number_n = $contact;
            }

            $opt = '88'.Str::substr($contact_number_n,0,3);  
            
            if (in_array($opt, $validprefix))
            {
                array_push($contacts, $contact_number_n);
            }

        }

        return $this->filecontacts = $contacts;
    }

    /**
     * Get total number valid contact in a uploaded file
     *
     * @return void
     */
    public function totalValidContactInAFile()
    {
        $contacts = [];

        $validnumbers = $this->validMobileFromFile($this->userid, $this->filecontacts);

        foreach($validnumbers as $contact)
        {
            array_push($contacts,$contact);
        }
            
        return count($contacts);
    }


    /**
     * Valid mobile number
     *
     * @param int $userid
     * @param int $groupid
     * @return void
     */
    public function validMobile($userid, $groupid)
    {
        $contacts = [];

        $newarr = [];
        
        $validprefix =  ["88017","88014","88013","88015","88018","88016","88019"];

        $groupinfo = $this->contactgroup->getGroupByClientAndId($userid, $groupid);

        $this->userid = $userid;

        $this->groupid = $groupid;

        foreach($groupinfo as $group)
        {
            foreach($group->contacts as $contact)
            {
                $checkcontact = Str::substr($contact->contact_number,0,2);
                $checkcontactwithplus = Str::substr($contact->contact_number,0,3);

                //Covert number in format like 01716xxxxxx if start with 88
                $contact_number_n = '';
                if ($checkcontact == 88 || $checkcontactwithplus == '+88')
                {
                    $contact_number_n = $checkcontact == 88 ? Str::substr($contact->contact_number,2,13) : Str::substr($contact->contact_number,3,13); 
                } else {
                    $contact_number_n = $contact->contact_number;
                }

                $opt = '88'.Str::substr($contact_number_n,0,3);  
                
                if (in_array($opt, $validprefix))
                {
                    array_push($contacts, $contact_number_n);
                }

            }

        }
        return $contacts;

    }


    /**
     * Valid mobile number
     *
     * @param int $userid
     * @param int $groupid
     * @return void
     */
    public function validMobileFromRequest(array $clientcontacts)
    {
        $contacts = [];

        $newarr = [];
        
        $validprefix =  ["88017","88014","88013","88015","88018","88016","88019"];


        foreach($clientcontacts as $contact)
        {
            $checkcontact = Str::substr($contact,0,2);
            $checkcontactwithplus = Str::substr($contact,0,3);

            //Covert number in format like 01716xxxxxx if start with 88
            $contact_number_n = '';
            if ($checkcontact == 88 || $checkcontactwithplus == '+88')
            {
                $contact_number_n = $checkcontact == 88 ? Str::substr($contact,2,13) : Str::substr($contact,3,13); 
            } else {
                $contact_number_n = $contact;
            }

            $opt = '88'.Str::substr($contact_number_n,0,3);  
            
            if (in_array($opt, $validprefix))
            {
                array_push($contacts, $contact_number_n);
            }

        }
        return $contacts;

    }

    /**
     * Get total number valid contact in a group
     *
     * @return void
     */
    public function totalValidContactInARequest(array $clientcontacts)
    {
        $contacts = [];

        $validnumbers = $this->validMobileFromRequest($clientcontacts);

        foreach($validnumbers as $contact)
        {
            array_push($contacts,$contact);
        }
            
        return count($contacts);
    }


    /**
     * Get total number valid contact in a group
     *
     * @return void
     */
    public function totalValidContactInAGroup()
    {
        $contacts = [];

        $validnumbers = $this->validMobile($this->userid, $this->groupid);

        foreach($validnumbers as $contact)
        {
            array_push($contacts,$contact);
        }
            
        return count($contacts);
    }

    /**
     * Determine sms message is unicode content | normal text content
     *
     * @param string $message
     * @return void
     */
    public function smsMessageType($message)
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
    public function manageSmsMessageCount($message)
    {
        $countmsg = Str::length($message); //strlen($message);

        $type = $this->smsMessageType($message);

        if($type=='text'){
           
            if ($countmsg <= 160) { 

                return $this->totalmsg = 1;  

            //} else if ($countmsg <= 310) { 
            } else if ($countmsg > 160 && $countmsg <= 306) { 
                
                return $this->totalmsg = 2;

            } else if ($countmsg <= 306) { 
                
                return $this->totalmsg = 2;

            } else if ($countmsg <= 460) {

                return $this->totalmsg = 3; 

            } else if ($countmsg <= 610) { 
                
                return $this->totalmsg = 4;

            } else if ($countmsg <= 760) { 
                
                return $this->totalmsg = 5;

            } else if ($countmsg <= 910) { 
                
                return $this->totalmsg = 6;

            } else { 
                
                return $this->totalmsg = 1000000000;

            } 
        } else {

            $countmsg= Str::length($message, 'UTF-8');//mb_strlen( $message,'UTF-8'); 

            if ($countmsg <= 70) {

                return $this->totalmsg = 1;  

            } else if ($countmsg > 70 && $countmsg <= 134) {

                return $this->totalmsg = 2;

            } else if ($countmsg <= 134) {

                return $this->totalmsg = 2;

            } else if ($countmsg <= 201) {

                return $this->totalmsg = 3; 

            } else if ($countmsg <= 268) {

                return $this->totalmsg = 4;

            } else if ($countmsg <= 335) {

                return $this->totalmsg = 5;

            } else if ($countmsg <= 402) {

                return $this->totalmsg = 6;

            } else if ($countmsg <= 469) {

                return $this->totalmsg = 7;

            } else if ($countmsg <= 536) {

                return $this->totalmsg = 8;

            } else {

                return $this->totalmsg = 1000000000;

            } 

        }
    }

    /**
     * Get sender id information
     *
     * @param int $senderid
     * @return void
     */
    public function getSenderIdInformationBySenderId($senderid)
    {
        return $this->senderid->getSenderIdById($senderid);
    }

    /**
     * Get sender id information
     *
     * @param int $senderid
     * @return void
     */
    public function getSenderIdInformationBySenderName($senderid)
    {
        return $this->senderid->getSenderIdByName($senderid);
    }



    public function smsSendToTelitalk(array $data){
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');
        
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $messType = ["text"=>"ASCII" , "flash"=>"UTF-8" , "unicode"=>"UTF-8"];

        
        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $date = \Carbon\Carbon::now();
        $teletalksenderid = $this->senderid->getTeletalkSenderIdByName($data['cli']);

        $post_values = [
            "user"		=> $teletalksenderid->user,//$data['user'],                //$optUrlUser[$optID],               
            "pass"		=> $teletalksenderid->password,//$data['pass'],                //$optUrlPass[$optID],  
            "op"		=> "SMS", 
            "mobile"	    => $data['mobile'],  
            //"cli"	  	    => $sender,     
            "charset"	=> $messType[$data['charset']], 
            "sms"		=> $data['sms'], 
        ];
          
        $post_string = "";
        foreach( $post_values as $key => $pvalue )
            { $post_string .= "$key=" . urlencode( $pvalue ) . "&"; }
        $post_string = rtrim( $post_string, "& " );
          
        $request = curl_init($data['post_url']); // initiate curl object
            curl_setopt($request, CURLOPT_HEADER, 0);  
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
            curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
            $post_response = curl_exec($request);  
        curl_close ($request);  
        
        
        $xml_object= simplexml_load_string($post_response); 
        $resultArr = explode(',', $xml_object); 
        $submitted_id =  @$resultArr[1];
        
        if($resultArr[0] == 'SUCCESS' &&  !empty($submitted_id) ) 
        { 
            $totalsms = explode(",",$data['mobile']);

            $date = \Carbon\Carbon::now();

            $backupstage = DB::table("backups_stage")->get();

            if ($backupstage[0]->status) {

                // $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                //                     ->where('remarks',$data['campaing'])
                //                     ->where('to_number',$data['mobile'])
                //                     ->where('status', false)->update([
                //                         'status' => true
                //                     ]);
                
                $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                ->where('remarks',$data['campaing'])
                ->where('to_number','LIKE', '%'.$data['mobile'].'%')
                ->where('status',false)->first();

                $sentsms->status = true;
                $sentsms->save();
            } else {

                // $sentsms = UserSentSms::where('user_id', $data['userid'])
                //                         ->where('remarks',$data['campaing'])
                //                         ->where('to_number',$data['mobile'])
                //                         ->where('status', false)->update([
                //                             'status' => true
                //                         ]);
                
                $sentsms = UserSentSms::where('user_id', $data['userid'])
                ->where('remarks',$data['campaing'])
                ->where('to_number','LIKE', '%'.$data['mobile'].'%')
                ->where('status',false)->first();

                $sentsms->status = true;
                $sentsms->save();
            }

            if (UserBalance::where('userid',$data['userid'])->exists()) {
                $userblance = UserBalance::where('userid',$data['userid'])->first();
                if ($data['senderidtype'] == 'mask') {
                    $userblance->mask += $data['messagecount'];
                }

                if ($data['senderidtype'] == 'nomask') {
                    $userblance->nonmask += $data['messagecount'];
                }

                if ($data['senderidtype'] == 'voice') {
                    $userblance->voice += $data['messagecount'];
                }
                $userblance->balance_date = Carbon::today();
                $userblance->save();
            } else {

                $currentdate = Carbon::today();

                $balusers = User::whereIn('id', function($query){
                    $query->select('user_id')
                    ->from('user_sent_smses');
                })->get();

                $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();

                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
                    && !$checkArchive->isEmpty()
                ) {
                    foreach($balusers as $user) {
                        $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                from archive_sent_smses 
                                                                where sms_catagory='mask'
                                                                and status = true
                                                                and user_id = $user->id) as 'mask',
        
                                                        (select sum(number_of_sms) 
                                                                from archive_sent_smses 
                                                                where sms_catagory='nomask'
                                                                and status = true
                                                                and user_id = $user->id) as 'nonmask',
        
                                                        (select sum(number_of_sms) 
                                                                from archive_sent_smses 
                                                                where sms_catagory='voice'
                                                                and status = true
                                                                and user_id = $user->id) as 'voice',
                                                        DATE(submitted_at) balance_date
        
                                                        FROM `user_sent_smses`
                                                        where status = true
                                                        and user_id = $user->id
                                    "));
        
                        $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
        
                        if ($checkbalance->isEmpty()) { 
                            DB::table("user_balance")->insert([
                                'userid' => $user->id,
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        } else {
                            
                            DB::table("user_balance")->where('userid',$user->id)->update([
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        }
                    }

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {

                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);

                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }

                } else {
                
                    foreach($balusers as $user) {
                        $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                from user_sent_smses 
                                                                where sms_catagory='mask'
                                                                and status = true
                                                                and user_id = $user->id) as 'mask',
        
                                                        (select sum(number_of_sms) 
                                                                from user_sent_smses 
                                                                where sms_catagory='nomask'
                                                                and status = true
                                                                and user_id = $user->id) as 'nonmask',
        
                                                        (select sum(number_of_sms) 
                                                                from user_sent_smses 
                                                                where sms_catagory='voice'
                                                                and status = true
                                                                and user_id = $user->id) as 'voice',
                                                        DATE(submitted_at) balance_date
        
                                                        FROM `user_sent_smses`
                                                        where status = true
                                                        and user_id = $user->id
                                    "));
        
                        $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
        
                        if ($checkbalance->isEmpty()) { 
                            DB::table("user_balance")->insert([
                                'userid' => $user->id,
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        } else {
                            
                            DB::table("user_balance")->where('userid',$user->id)->update([
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        }
                    }

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {

                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);

                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }
                }
            }

            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
            ) {

                $backupstage = DB::table("backups_stage")->get();

                if ($backupstage[0]->status) {

                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
                    ) {
                        $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                        $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                        $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                        
                        $userbalance = UserBalance::where('userid',$data['userid'])->first();
                        $userbalance->mask += $currentmasksms;
                        $userbalance->nonmask += $currentnonmasksms;
                        $userbalance->voice += $currentvoicesms;
                        $userbalance->save();
                    }

                    CheckRuntimeBalance::where('userid', $data['userid'])->update([
                        'ischecked' => true
                    ]);

                } else {
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
                    ) {
                        $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                        $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                        $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                        
                        $userbalance = UserBalance::where('userid',$data['userid'])->first();
                        $userbalance->mask += $currentmasksms;
                        $userbalance->nonmask += $currentnonmasksms;
                        $userbalance->voice += $currentvoicesms;
                        $userbalance->save();
                    }

                    CheckRuntimeBalance::where('userid', $data['userid'])->update([
                        'ischecked' => true
                    ]);
                }

            }

            /*$smscount = UserCountSms::firstOrNew([
                'campaing_name' => $data['campaing'],
                'sms_category' => $data['senderidtype']
            ]);
            $smscount->user_id = $data['userid'];
            $smscount->sms_count += ($data['messagecount']*count($totalsms));
            $smscount->month_name =  $date->format('F');
            $smscount->year_name = $date->format('Y');
            $smscount->owner_id = $data['owner_id'];
            $smscount->owner_type = $data['owner_type'];
            $smscount->save();
            */

            //Need to start later
            /*if ($data['userid'] == 32) {
                $client = new Client([
                    'verify' => false
                ]);

                //foreach(explode(",",$data['msisdn']) as $record) {
                    $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                        'query' => [ 
                            'MessageId' => $camid,//$resarr['MessageId'],
                            'MessageStatus' => 'Delivered',
                            'MobileNumber' => $data['mobile'],
                            'SenderId' => $data['cli'],
                            'ErrorCode' => 0,
                            'Message' => $data['sms'],
                            'DoneDate' => $date->addSecond(2)
                        ]
                    ]);

                    //return $clientresponse->getBody();
                //}
            }*/

            if ($data['userid'] == 98) {
                
                session()->put('multisms','no');
                session()->put('errorflug','success');
                session()->put('sender',$data['cli']);
                session()->put('destination',$data['mobile']);
                session()->put('messageId',$camid);
            }
            
            session()->put('sendsuccess','Sms sent successfully');
            session()->forget('senderr');

            return response()->json(['msg' => $resultArr[0].",".$resultArr[1]],200); 
        } else {

            //return $smssend = 'error';
            $date = \Carbon\Carbon::now();
            DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'Teletalk',
                    'senderid' => $data['cli'],
                    'error_description' => json_encode(@$resultArr),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                if ($data['userid'] == 98) {
                    
                    session()->put('multisms','no');
                    session()->put('errorflug','error');
                    session()->put('sender',$data['cli']);
                    session()->put('destination',$data['mobile']);
                    session()->put('messageId',$camid);
                }

                session()->put('senderr','Gateway error,we are coming back, within very short time :)');
                session()->forget('sendsuccess');
        }
    }

    public function smsSendToGp(array $data)
    {
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');

        
        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $messType = ["text"=>1 , "flash"=>2 , "unicode"=>3]; 

        $totalsms = explode(",",$data['msisdn']);

        if($messType[$data['messagetype']]==3)
        {
            //unicode message
            $msgStr =  bin2hex(mb_convert_encoding( $data['message'], 'UTF-16'));

            $post_values = [ 
                "username"		=> $data['username'],               
                "password"		=> $data['password'],  
                "apicode"		=> "6", 
                "msisdn"	    => $data['msisdn'],
                
                "countrycode"	=> "880",
                "messageid"		=> 0,
                "cli"	  	    => $data['cli'],     
                "messagetype"	=> $messType[$data['messagetype']], 
                "message"		=> $msgStr,
            ];
                
            $post_string = "";
            foreach( $post_values as $key => $pvalue )
                { $post_string .= "$key=" . urlencode( $pvalue ) . "&"; }
            $post_string = rtrim( $post_string, "& " );
                
            $request = curl_init($data['post_url']); // initiate curl object
                curl_setopt($request, CURLOPT_HEADER, 0);  
                curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
                curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
                curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
                $post_response = curl_exec($request);  
            curl_close ($request);  
            
            
            $resultArr = explode(',', $post_response); 
            $resultArrStr = implode(",",$resultArr);

            $resarr = [];
            $shutid = explode(",",str_replace("\n",",",$resultArrStr));

            $recarr = [];

            $submitted_id =  @$resultArr[1];
            
            if($resultArr[1]!=200 )
            { 
                $smssend = 'error';
                $date = \Carbon\Carbon::now();
                DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'gp',
                    'senderid' => $data['cli'],
                    'error_description' => json_encode(@$resultArr),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                session()->put('senderr',"Sms send error, ".$submitted_id);

                session()->forget('sendsuccess');

                if ($data['userid'] == 32) {
                    /*foreach($shutid as $resstr) {
                        if (strlen($resstr)<=3) {
                            $resarr['MessageStatus'] = $resstr;
                        }
        
                        if (strlen($resstr) >= 11 && strlen($resstr)<=13) {
                            $resarr['MobileNumber'] = $resstr;
                        }
        
                        if (strlen($resstr)>13) {
                            $resarr['MessageId'] = $resstr;
                        }
        
                        if (array_key_exists("MobileNumber",$resarr) && 
                            array_key_exists("MessageId",$resarr) && 
                            array_key_exists("MessageStatus",$resarr) )
                        {
                                $recarr[] = [
                                                'MessageId' => $camid,//$resarr['MessageId'],
                                                'MessageStatus' => $resarr['MessageStatus'],
                                                'MobileNumber' => $resarr['MobileNumber'],
                                                'SenderId' => $data['cli'],
                                                'ErrorCode' => 0,
                                                'Message'   => $msgStr,
                                                'DoneDate' => Carbon::now()
                                ];
        
                                unset($resarr);
                        }
                    }

                    $client = new Client([
                        'verify' => false
                    ]);

                    foreach($recarr as $record) {
                        $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                            'query' => [ 
                                'MessageId' => $camid,//$resarr['MessageId'],
                                'MessageStatus' => $record['MessageStatus'],
                                'MobileNumber' => $record['MobileNumber'],
                                'SenderId' => $record['SenderId'],
                                'ErrorCode' => $record['ErrorCode'],
                                'Message' => $record['Message'],
                                'DoneDate' => $record['DoneDate']
                            ]
                        ]);
                    }*/
                }
                
                if ($data['userid'] == 98) {

                    session()->put('multisms','yes');
                    session()->put('errorflug','error');
                    session()->put('sender',$data['cli']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                
            } else {
                
                $totalsms = explode(",",$data['msisdn']);

                $date = \Carbon\Carbon::now();
                
                /*$smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += ($data['messagecount']*count($totalsms));
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();
                */


                $backupstage = DB::table("backups_stage")->get();

                if ($backupstage[0]->status) {

                    $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->whereIn('to_number',explode(",",$data['msisdn']))
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);

                    
                } else {

                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->whereIn('to_number',explode(",",$data['msisdn']))
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);
                }

                if (UserBalance::where('userid',$data['userid'])->exists()) {
                    $userblance = UserBalance::where('userid',$data['userid'])->first();
                    if ($data['senderidtype'] == 'mask') {
                        $userblance->mask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'nomask') {
                        $userblance->nonmask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'voice') {
                        $userblance->voice += ($data['messagecount']*count($totalsms));
                    }
                    $userblance->balance_date = Carbon::today();
                    $userblance->save();
                } else {
    
                    $currentdate = Carbon::today();
    
                    $balusers = User::whereIn('id', function($query){
                        $query->select('user_id')
                        ->from('user_sent_smses');
                    })->get();
    
                    $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
    
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        && !$checkArchive->isEmpty()
                    ) {
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
    
                    } else {
                    
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
                    }
                }
    
                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                ) {
    
                    $backupstage = DB::table("backups_stage")->get();
    
                    if ($backupstage[0]->status) {
    
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
    
                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }
    
                }

                
                //Need to start later
                /*if ($data['userid'] == 32) {
                    //This will not start
                    /*foreach($shutid as $resstr) {
                        if (strlen($resstr)<=3) {
                            $resarr['MessageStatus'] = $resstr;
                        }
        
                        if (strlen($resstr) >= 11 && strlen($resstr)<=13) {
                            $resarr['MobileNumber'] = $resstr;
                        }
        
                        if (strlen($resstr)>13) {
                            $resarr['MessageId'] = $resstr;
                        }
        
                        if (array_key_exists("MobileNumber",$resarr) && 
                            array_key_exists("MessageId",$resarr) && 
                            array_key_exists("MessageStatus",$resarr) )
                        {
                                $recarr[] = [
                                                'MessageId' => $camid,//$resarr['MessageId'],
                                                'MessageStatus' => $resarr['MessageStatus'],
                                                'MobileNumber' => $resarr['MobileNumber'],
                                                'SenderId' => $data['cli'],
                                                'ErrorCode' => Null,
                                                'Message'   => $msgStr,
                                                'DoneDate' => Carbon::now()
                                ];
        
                                unset($resarr);
                        }
                    }*/

            /*        $client = new Client([
                        'verify' => false
                    ]);

                    foreach(explode(",",$data['msisdn']) as $record) {
                        $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                            'query' => [ 
                                'MessageId' => $camid,//$resarr['MessageId'],
                                'MessageStatus' => 'Delivered',
                                'MobileNumber' => $record,
                                'SenderId' => $data['cli'],
                                'ErrorCode' => 0,
                                'Message' => $msgStr,
                                'DoneDate' => $date->addSecond(2)
                            ]
                        ]);
                    }
                }
                */

                if ($data['userid'] == 98) {

                    session()->put('multisms','yes');
                    session()->put('errorflug','success');
                    session()->put('sender',$data['cli']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                session()->put('sendsuccess','Sms sent successfully');
                session()->forget('senderr');
                return response()->json(['msg' => @$resultArr[0].",".@$resultArr[1]],200);
            }
        }else {
            $msgStr =	$data['message'];
        
            $post_values = [ 
                "username"		=> $data['username'],               
                "password"		=> $data['password'],  
                "apicode"		=> "6", 
                "msisdn"	    => $data['msisdn'],
                
                "countrycode"	=> "880",
                "messageid"		=> 0,
                "cli"	  	    => $data['cli'],     
                "messagetype"	=> $messType[$data['messagetype']], 
                "message"		=> $msgStr,
            
                
            ];
                
            $post_string = "";
            foreach( $post_values as $key => $pvalue )
                { $post_string .= "$key=" . urlencode( $pvalue ) . "&"; }
            $post_string = rtrim( $post_string, "& " );
                
            $request = curl_init($data['post_url']); // initiate curl object
                curl_setopt($request, CURLOPT_HEADER, 0);  
                curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
                curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
                curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
                $post_response = curl_exec($request);  
            curl_close ($request);  
            
            
            $resultArr = explode(',', $post_response); 
            $resultArrStr = implode(",",$resultArr);

            $resarr = [];
            $shutid = explode(",",str_replace("\n",",",$resultArrStr));

            $recarr = [];
            

            $submitted_id =  @$resultArr[1];
            
            if($resultArr[1]!=200 )
            { 
                $smssend = 'error';
                $date = \Carbon\Carbon::now();
                DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'gp',
                    'senderid' => $data['cli'],
                    'error_description' => json_encode(@$resultArr),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                /*if ($data['userid'] == 32) {
                    foreach($shutid as $resstr) {
                        if (strlen($resstr)<=3) {
                            $resarr['MessageStatus'] = $resstr;
                        }
        
                        if (strlen($resstr) >= 11 && strlen($resstr)<=13) {
                            $resarr['MobileNumber'] = $resstr;
                        }
        
                        if (strlen($resstr)>13) {
                            $resarr['MessageId'] = $resstr;
                        }
        
                        if (array_key_exists("MobileNumber",$resarr) && 
                            array_key_exists("MessageId",$resarr) && 
                            array_key_exists("MessageStatus",$resarr) )
                        {
                                $recarr[] = [
                                                'MessageId' => $camid,//$resarr['MessageId'],
                                                'MessageStatus' => $resarr['MessageStatus'],
                                                'MobileNumber' => $resarr['MobileNumber'],
                                                'SenderId' => $data['cli'],
                                                'ErrorCode' => Null,
                                                'Message'   => $msgStr,
                                                'DoneDate' => Carbon::now()
                                ];
        
                                unset($resarr);
                        }
                    }

                    $client = new Client([
                        'verify' => false
                    ]);

                    foreach($recarr as $record) {
                        $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                            'query' => [ 
                                'MessageId' => $camid,//$resarr['MessageId'],
                                'MessageStatus' => $record['MessageStatus'],
                                'MobileNumber' => $record['MobileNumber'],
                                'SenderId' => $record['SenderId'],
                                'ErrorCode' => 0,
                                'Message' => $record['Message'],
                                'DoneDate' => $record['DoneDate']
                            ]
                        ]);
                    }
                }*/

                if ($data['userid'] == 98) {
                    session()->put('multisms','yes');
                    session()->put('errorflug','error');
                    session()->put('sender',$data['cli']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                session()->put('senderr',"Sms send error, ".$submitted_id);
                session()->forget('sendsuccess');
            } else {
                $totalsms = explode(",",$data['msisdn']);
                $date = \Carbon\Carbon::now();
                
                /*$smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += ($data['messagecount']*count($totalsms));
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();
                */

                $backupstage = DB::table("backups_stage")->get();

                if ($backupstage[0]->status) {

                    $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->whereIn('to_number',explode(",",$data['msisdn']))
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);
                } else {
                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->whereIn('to_number',explode(",",$data['msisdn']))
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);
                }

                if (UserBalance::where('userid',$data['userid'])->exists()) {
                    $userblance = UserBalance::where('userid',$data['userid'])->first();
                    if ($data['senderidtype'] == 'mask') {
                        $userblance->mask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'nomask') {
                        $userblance->nonmask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'voice') {
                        $userblance->voice += ($data['messagecount']*count($totalsms));
                    }
                    $userblance->balance_date = Carbon::today();
                    $userblance->save();
                } else {
    
                    $currentdate = Carbon::today();
    
                    $balusers = User::whereIn('id', function($query){
                        $query->select('user_id')
                        ->from('user_sent_smses');
                    })->get();
    
                    $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
    
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        && !$checkArchive->isEmpty()
                    ) {
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
    
                    } else {
                    
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
                    }
                }
    
                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                ) {
    
                    $backupstage = DB::table("backups_stage")->get();
    
                    if ($backupstage[0]->status) {
    
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
    
                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }
    
                }

                //Need to start later
                /*if ($data['userid'] == 32) {
                    //This will not start
                    /*foreach($shutid as $resstr) {
                        if (strlen($resstr)<=3) {
                            $resarr['MessageStatus'] = $resstr;
                        }
        
                        if (strlen($resstr) >= 11 && strlen($resstr)<=13) {
                            $resarr['MobileNumber'] = $resstr;
                        }
        
                        if (strlen($resstr)>13) {
                            $resarr['MessageId'] = $resstr;
                        }
        
                        if (array_key_exists("MobileNumber",$resarr) && 
                            array_key_exists("MessageId",$resarr) && 
                            array_key_exists("MessageStatus",$resarr) )
                        {
                                $recarr[] = [
                                                'MessageId' => $camid,//$resarr['MessageId'],
                                                'MessageStatus' => $resarr['MessageStatus'],
                                                'MobileNumber' => $resarr['MobileNumber'],
                                                'SenderId' => $data['cli'],
                                                'ErrorCode' => Null,
                                                'Message'   => $msgStr,
                                                'DoneDate' => Carbon::now()
                                ];
        
                                unset($resarr);
                        }
                    }*/

                    /*$client = new Client([
                        'verify' => false
                    ]);

                    foreach(explode(",",$data['msisdn']) as $record) {
                        $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                            'query' => [ 
                                'MessageId' => $camid,//$resarr['MessageId'],
                                'MessageStatus' => 'Delivered',
                                'MobileNumber' => $record,
                                'SenderId' => $data['cli'],
                                'ErrorCode' => 0,
                                'Message' => $msgStr,
                                'DoneDate' => $date->addSecond(2)
                            ]
                        ]);
                    }
                }*/

                if ($data['userid'] == 98) {

                    session()->put('multisms','yes');
                    session()->put('errorflug','success');
                    session()->put('sender',$data['cli']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                session()->put('sendsuccess','Sms sent successfully');
                session()->forget('senderr');
                return response()->json(['msg' => @$resultArr[0].",".@$resultArr[1]],200);
            }
        }
    }

    public function smsSendToBlink(array $data)
    {
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');

        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $date = \Carbon\Carbon::now();

        $totalsms = explode(",",$data['msisdn']);

        $SendAPI = $data['post_url']."&msisdn=".$data['msisdn']."&sender=".urlencode($data['sender'])."&message=".urlencode($data['message']); 
		try{	  
            $output = file($SendAPI); 
            $result_sms = end($output);   //Success Count : 1 and Fail Count : 0
            
            
            
            $resultArr = explode('and', $result_sms); 

            $submitted_id =  'BL-1';
            
            if(  @$resultArr[1]!=' Fail Count : 0' )
            { 
                $smssend = 'error'; 
                $date = \Carbon\Carbon::now();
                DB::table('sms_send_errors')
                    ->insert([
                        'operator_type' => 'blink',
                        'senderid' => $data['sender'],
                        'error_description' => json_decode(@$resultArr),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);  

                    if ($data['userid'] == 98) {

                        session()->put('multisms','yes');
                        session()->put('errorflug','error');
                        session()->put('sender',$data['sender']);
                        session()->put('destination',explode(",",$data['msisdn']));
                        session()->put('messageId',$camid);
                        
                    }

                    session()->put('senderr', "Sms send error ".$resultArr[0]." ".$resultArr[1]);
                    session()->forget('sendsuccess');
            } else {
                $totalsms = explode(",",$data['msisdn']);
                $date = \Carbon\Carbon::now();
                
                /*$smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += ($data['messagecount']*count($totalsms));
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();
                */

                $backupstage = DB::table("backups_stage")->get();

                if ($backupstage[0]->status) {

                    $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                ->where('remarks',$data['campaing'])
                                ->whereIn('to_number',explode(",",$data['msisdn']))
                                ->where('status', false)->update([
                                    'status' => true
                                ]);
                } else {
                    
                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                ->where('remarks',$data['campaing'])
                                ->whereIn('to_number',explode(",",$data['msisdn']))
                                ->where('status', false)->update([
                                    'status' => true
                                ]);
                }

                if (UserBalance::where('userid',$data['userid'])->exists()) {
                    $userblance = UserBalance::where('userid',$data['userid'])->first();
                    if ($data['senderidtype'] == 'mask') {
                        $userblance->mask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'nomask') {
                        $userblance->nonmask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'voice') {
                        $userblance->voice += ($data['messagecount']*count($totalsms));
                    }
                    $userblance->balance_date = Carbon::today();
                    $userblance->save();
                } else {
    
                    $currentdate = Carbon::today();
    
                    $balusers = User::whereIn('id', function($query){
                        $query->select('user_id')
                        ->from('user_sent_smses');
                    })->get();
    
                    $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
    
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        && !$checkArchive->isEmpty()
                    ) {
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
    
                    } else {
                    
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
                    }
                }
    
                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                ) {
    
                    $backupstage = DB::table("backups_stage")->get();
    
                    if ($backupstage[0]->status) {
    
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
    
                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }
    
                }

                //Need to start later
                /*
                if ($data['userid'] == 32) {
                    $client = new Client([
                        'verify' => false
                    ]);
    
                    foreach(explode(",",$data['msisdn']) as $record) {
                        $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                            'query' => [ 
                                'MessageId' => $camid,//$resarr['MessageId'],
                                'MessageStatus' => 'Delivered',
                                'MobileNumber' => $record,
                                'SenderId' => $data['sender'],
                                'ErrorCode' => 0,
                                'Message' => $data['message'],
                                'DoneDate' => $date->addSecond(2)
                            ]
                        ]);
                    }
                }*/

                if ($data['userid'] == 98) {
                    session()->put('multisms','yes');
                    session()->put('errorflug','success');
                    session()->put('sender',$data['sender']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                }
                
                session()->put('sendsuccess','Sms sent successfully');
                session()->forget('senderr');
                return response()->json(['msg' => @$resultArr[0]." message successfully send"], 200);
            }
        } catch(\Exception $e) {
            return response()->json(['errmsg' => $e->getMessage()],200);
        }
    }


    public function smsSendToRobi(array $data)
    {
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');


        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $date = \Carbon\Carbon::now();

        $SendAPI = htmlspecialchars_decode($data['post_url']."&To=".$data['To']."&From=".urlencode($data['From'])."&Message=".urlencode($data['Message'])); 

        $totalsms = explode(",",$data['To']);
        
        try{
            $output = file($SendAPI); 
            $result_sms = end($output); 
            
            $xml_object=simplexml_load_string( $result_sms); 
            $xml_array=json_decode(json_encode($xml_object),true);
            
            //return array_keys($xml_array['ServiceClass']);
            // $pieces = explode(' ', $result_sms); 
            //return $submitted_id = @$xml_array['ServiceClass'][0]['MessageId'];

            if (array_key_exists('Status', $xml_array['ServiceClass']))
            {
                if(  @$xml_array['ServiceClass']['ErrorCode']!='0' )
                { 
                    
                    $smssend = 'error'; 
                    $date = \Carbon\Carbon::now();
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel',
                            'senderid' => $data['From'],
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        if ($data['userid'] == 98) {
                            session()->put('multisms','yes');
                            session()->put('errorflug','error');
                            session()->put('sender',$data['From']);
                            session()->put('destination',explode(",",$data['To']));
                            session()->put('messageId',$camid);
                            
                        }
                        
                        session()->put('senderr',@$xml_array['ServiceClass']['ErrorText'].@$xml_array['ServiceClass']['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    $totalsms = explode(",",$data['To']);
                    $date = \Carbon\Carbon::now();
                    
                    /*$smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += ($data['messagecount']*count($totalsms));
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();
                    */

                    

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {
    
                        $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    } else {
                        
                        $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    }

                    if (UserBalance::where('userid',$data['userid'])->exists()) {
                        $userblance = UserBalance::where('userid',$data['userid'])->first();
                        if ($data['senderidtype'] == 'mask') {
                            $userblance->mask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'nomask') {
                            $userblance->nonmask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'voice') {
                            $userblance->voice += ($data['messagecount']*count($totalsms));
                        }
                        $userblance->balance_date = Carbon::today();
                        $userblance->save();
                    } else {
        
                        $currentdate = Carbon::today();
        
                        $balusers = User::whereIn('id', function($query){
                            $query->select('user_id')
                            ->from('user_sent_smses');
                        })->get();
        
                        $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
        
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            && !$checkArchive->isEmpty()
                        ) {
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
        
                        } else {
                        
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
                        }
                    }
        
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                    ) {
        
                        $backupstage = DB::table("backups_stage")->get();
        
                        if ($backupstage[0]->status) {
        
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
        
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
        
                    }

                    //Need to start later
                    /*if ($data['userid'] == 32) {
                        $client = new Client([
                            'verify' => false
                        ]);
        
                        foreach(explode(",",$data['To']) as $record) {
                            $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                                'query' => [ 
                                    'MessageId' => $camid,//$resarr['MessageId'],
                                    'MessageStatus' => 'Delivered',
                                    'MobileNumber' => $record,
                                    'SenderId' => $data['From'],
                                    'ErrorCode' => 0,
                                    'Message' => $data['Message'],
                                    'DoneDate' => $date->addSecond(2)
                                ]
                            ]);
                        }
                    }*/

                    if ($data['userid'] == 98) {
                        session()->put('multisms','yes');
                        session()->put('errorflug','success');
                        session()->put('sender',$data['From']);
                        session()->put('destination',explode(",",$data['To']));
                        session()->put('messageId',$camid);
                        
                    }

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            } else {
                if(  @$xml_array['ServiceClass'][0]['ErrorCode']!='0' )
                { 
                    
                    $smssend = 'error'; 
                    $date = \Carbon\Carbon::now();
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel',
                            'senderid' => $data['From'],
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        if ($data['userid'] == 98) {
                            session()->put('multisms','yes');
                            session()->put('errorflug','error');
                            session()->put('sender',$data['From']);
                            session()->put('destination',explode(",",$data['To']));
                            session()->put('messageId',$camid);
                            
                        }
                        
                        session()->put('senderr',@$xml_array['ServiceClass'][0]['ErrorText'].@$xml_array['ServiceClass'][0]['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    $totalsms = explode(",",$data['To']);
                    $date = \Carbon\Carbon::now();
                    /*$smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += ($data['messagecount']*count($totalsms));
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();
                    */

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {
    
                        $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
    
                        
                    } else {
    
                        $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    }

                    if (UserBalance::where('userid',$data['userid'])->exists()) {
                        $userblance = UserBalance::where('userid',$data['userid'])->first();
                        if ($data['senderidtype'] == 'mask') {
                            $userblance->mask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'nomask') {
                            $userblance->nonmask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'voice') {
                            $userblance->voice += ($data['messagecount']*count($totalsms));
                        }
                        $userblance->balance_date = Carbon::today();
                        $userblance->save();
                    } else {
        
                        $currentdate = Carbon::today();
        
                        $balusers = User::whereIn('id', function($query){
                            $query->select('user_id')
                            ->from('user_sent_smses');
                        })->get();
        
                        $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
        
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            && !$checkArchive->isEmpty()
                        ) {
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
        
                        } else {
                        
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
                        }
                    }
        
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                    ) {
        
                        $backupstage = DB::table("backups_stage")->get();
        
                        if ($backupstage[0]->status) {
        
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
        
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
        
                    }

                    //Need to start later
                    /*if ($data['userid'] == 32) {
                        $client = new Client([
                            'verify' => false
                        ]);
        
                        foreach(explode(",",$data['To']) as $record) {
                            $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                                'query' => [ 
                                    'MessageId' => $camid,//$resarr['MessageId'],
                                    'MessageStatus' => 'Delivered',
                                    'MobileNumber' => $record,
                                    'SenderId' => $data['From'],
                                    'ErrorCode' => 0,
                                    'Message' => $data['Message'],
                                    'DoneDate' => $date->addSecond(2)
                                ]
                            ]);
                        }
                    }*/

                    if ($data['userid'] == 98) {
                        session()->put('multisms','yes');
                        session()->put('errorflug','success');
                        session()->put('sender',$data['From']);
                        session()->put('destination',explode(",",$data['To']));
                        session()->put('messageId',$camid);
                        
                    }

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            }
            
        } catch(\Exception $e) {
            session()->put('senderr','There is an error, please call at support');
            //return response()->json(['errmsg' => $e->getMessage()],200);
            return response()->json(['errmsg' => 'There is an error, please call at support'],406);
        }
    }

    public function smsSendToRobiNonMask(array $data)
    {
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');

        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $date = \Carbon\Carbon::now();

        $SendAPI = htmlspecialchars_decode($data['post_url']."&To=".$data['To']."&From=".urlencode($data['From'])."&Message=".urlencode($data['Message'])); 

        $totalsms = explode(",",$data['To']);
        
        try{
            $output = file($SendAPI); 
            $result_sms = end($output); 
            
            $xml_object=simplexml_load_string( $result_sms); 
            $xml_array=$xml_array=json_decode(json_encode($xml_object),true);
            
            // $pieces = explode(' ', $result_sms); 
            if (array_key_exists('Status', $xml_array['ServiceClass']))
            {
                if(  @$xml_array['ServiceClass']['ErrorCode']!='0' )
                { 
                    
                    $smssend = 'error'; 
                    $date = \Carbon\Carbon::now();
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel-nonmask',
                            'senderid' => $data['From'],
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        if ($data['userid'] == 98) {
                            session()->put('multisms','yes');
                            session()->put('errorflug','error');
                            session()->put('sender',$data['From']);
                            session()->put('destination',explode(",",$data['To']));
                            session()->put('messageId',$camid);
                            
                        }
                        
                        session()->put('senderr',@$xml_array['ServiceClass']['ErrorText'].@$xml_array['ServiceClass']['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    $totalsms = explode(",",$data['To']);
                    $date = \Carbon\Carbon::now();
                    /*$smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += ($data['messagecount']*count($totalsms));
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();
                    */

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {
    
                        $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    } else {

                        
                        $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    }

                    if (UserBalance::where('userid',$data['userid'])->exists()) {
                        $userblance = UserBalance::where('userid',$data['userid'])->first();
                        if ($data['senderidtype'] == 'mask') {
                            $userblance->mask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'nomask') {
                            $userblance->nonmask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'voice') {
                            $userblance->voice += ($data['messagecount']*count($totalsms));
                        }
                        $userblance->balance_date = Carbon::today();
                        $userblance->save();
                    } else {
        
                        $currentdate = Carbon::today();
        
                        $balusers = User::whereIn('id', function($query){
                            $query->select('user_id')
                            ->from('user_sent_smses');
                        })->get();
        
                        $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
        
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            && !$checkArchive->isEmpty()
                        ) {
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
        
                        } else {
                        
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
                        }
                    }
        
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                    ) {
        
                        $backupstage = DB::table("backups_stage")->get();
        
                        if ($backupstage[0]->status) {
        
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
        
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
        
                    }

                    //Need to start later
                    /*if ($data['userid'] == 32) {
                        $client = new Client([
                            'verify' => false
                        ]);
        
                        foreach(explode(",",$data['To']) as $record) {
                            $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                                'query' => [ 
                                    'MessageId' => $camid,//$resarr['MessageId'],
                                    'MessageStatus' => 'Delivered',
                                    'MobileNumber' => $record,
                                    'SenderId' => $data['From'],
                                    'ErrorCode' => 0,
                                    'Message' => $data['Message'],
                                    'DoneDate' => $date->addSecond(2)
                                ]
                            ]);
                        }
                    }*/

                    if ($data['userid'] == 98) {
                        session()->put('multisms','yes');
                        session()->put('errorflug','success');
                        session()->put('sender',$data['From']);
                        session()->put('destination',explode(",",$data['To']));
                        session()->put('messageId',$camid);
                        
                    }

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            } else {
                if(  @$xml_array['ServiceClass'][0]['ErrorCode']!='0' )
                { 
                    
                    $smssend = 'error'; 
                    $date = \Carbon\Carbon::now();
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel-nonmask',
                            'senderid' => $data['From'],
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        if ($data['userid'] == 98) {
                            session()->put('multisms','yes');
                            session()->put('errorflug','error');
                            session()->put('sender',$data['From']);
                            session()->put('destination',explode(",",$data['To']));
                            session()->put('messageId',$camid);
                            
                        }
                        
                        session()->put('senderr',@$xml_array['ServiceClass'][0]['ErrorText'].@$xml_array['ServiceClass'][0]['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    $totalsms = explode(",",$data['To']);
                    $date = \Carbon\Carbon::now();
                    /*$smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += ($data['messagecount']*count($totalsms));
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();
                    */

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {
    
                        $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    } else {
    
                        
                        $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->whereIn('to_number',explode(",",$data['To']))
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    }

                    if (UserBalance::where('userid',$data['userid'])->exists()) {
                        $userblance = UserBalance::where('userid',$data['userid'])->first();
                        if ($data['senderidtype'] == 'mask') {
                            $userblance->mask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'nomask') {
                            $userblance->nonmask += ($data['messagecount']*count($totalsms));
                        }
        
                        if ($data['senderidtype'] == 'voice') {
                            $userblance->voice += ($data['messagecount']*count($totalsms));
                        }
                        $userblance->balance_date = Carbon::today();
                        $userblance->save();
                    } else {
        
                        $currentdate = Carbon::today();
        
                        $balusers = User::whereIn('id', function($query){
                            $query->select('user_id')
                            ->from('user_sent_smses');
                        })->get();
        
                        $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
        
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            && !$checkArchive->isEmpty()
                        ) {
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from archive_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
        
                        } else {
                        
                            foreach($balusers as $user) {
                                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='mask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'mask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='nomask'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'nonmask',
                
                                                                (select sum(number_of_sms) 
                                                                        from user_sent_smses 
                                                                        where sms_catagory='voice'
                                                                        and status = true
                                                                        and user_id = $user->id) as 'voice',
                                                                DATE(submitted_at) balance_date
                
                                                                FROM `user_sent_smses`
                                                                where status = true
                                                                and user_id = $user->id
                                            "));
                
                                $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
                
                                if ($checkbalance->isEmpty()) { 
                                    DB::table("user_balance")->insert([
                                        'userid' => $user->id,
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                } else {
                                    
                                    DB::table("user_balance")->where('userid',$user->id)->update([
                                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                        'balance_date' => $currentdate
                                    ]);
                                }
                            }
        
                            $backupstage = DB::table("backups_stage")->get();
        
                            if ($backupstage[0]->status) {
        
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
        
                            } else {
                                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                                ->where('ischecked',false)->exists()
                                ) {
                                    $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                    $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                    $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                    
                                    $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                    $userbalance->mask += $currentmasksms;
                                    $userbalance->nonmask += $currentnonmasksms;
                                    $userbalance->voice += $currentvoicesms;
                                    $userbalance->save();
                                }
        
                                CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                    'ischecked' => true
                                ]);
                            }
                        }
                    }
        
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                    ) {
        
                        $backupstage = DB::table("backups_stage")->get();
        
                        if ($backupstage[0]->status) {
        
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
        
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
        
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
        
                    }

                    //Need to start later
                    /*if ($data['userid'] == 32) {
                        $client = new Client([
                            'verify' => false
                        ]);
        
                        foreach(explode(",",$data['To']) as $record) {
                            $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                                'query' => [ 
                                    'MessageId' => $camid,//$resarr['MessageId'],
                                    'MessageStatus' => 'Delivered',
                                    'MobileNumber' => $record,
                                    'SenderId' => $data['From'],
                                    'ErrorCode' => 0,
                                    'Message' => $data['Message'],
                                    'DoneDate' => $date->addSecond(2)
                                ]
                            ]);
                        }
                    }*/

                    if ($data['userid'] == 98) {

                        session()->put('multisms','yes');
                        session()->put('errorflug','success');
                        session()->put('sender',$data['From']);
                        session()->put('destination',explode(",",$data['To']));
                        session()->put('messageId',$camid);
                        
                    }

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            }
        } catch(\Exception $e) {
            session()->put('senderr','There is an error, please call at support');
            //return response()->json(['errmsg' => $e->getMessage()],200);
            return response()->json(['errmsg' => 'There is an error, please call at support'],406);
        }
    }

    public function smsSendToEasyWeb(array $data){
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');

        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $date = \Carbon\Carbon::now();

        $totalsms = explode(",",$data['msisdn']);

        $SendAPI = $data['post_url']."&contacts=".$data['msisdn']."&senderid=".urlencode($data['sender'])."&msg=".urlencode($data['message']); 
			  
        $output = file($SendAPI); 
        $result_sms = end($output);   //Success Count : 1 and Fail Count : 0
        
        
        
        $resultArr = json_decode($result_sms,true); 
          
        if(  $resultArr['code'] == 445080 )
        { 
            $smssend = 'error'; 
            $date = \Carbon\Carbon::now();
            DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'EasyWeb',
                    'senderid' => $data['sender'],
                    'error_description' => $resultArr['message'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);  

                if ($data['userid'] == 98) {

                    session()->put('multisms','yes');
                    session()->put('errorflug','error');
                    session()->put('sender',$data['sender']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                session()->put('senderr',$resultArr['message']);
        } else {
            $totalsms = explode(",",$data['msisdn']);
            $date = \Carbon\Carbon::now();
            /*$smscount = UserCountSms::firstOrNew([
                'campaing_name' => $data['campaing'],
                'sms_category' => $data['senderidtype']
            ]);
            $smscount->user_id = $data['userid'];
            $smscount->sms_count += ($data['messagecount']*count($totalsms));
            $smscount->month_name =  $date->format('F');
            $smscount->year_name = $date->format('Y');
            $smscount->owner_id = $data['owner_id'];
            $smscount->owner_type = $data['owner_type'];
            $smscount->save();
            */

            $backupstage = DB::table("backups_stage")->get();

            if ($backupstage[0]->status) {

                $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                    ->where('remarks',$data['campaing'])
                                    ->whereIn('to_number',explode(",",$data['msisdn']))
                                    ->where('status', false)
                                    ->update([
                                        'status' => true
                                    ]);
            } else {

                $sentsms = UserSentSms::where('user_id', $data['userid'])
                                    ->where('remarks',$data['campaing'])
                                    ->whereIn('to_number',explode(",",$data['msisdn']))
                                    ->where('status', false)
                                    ->update([
                                        'status' => true
                                    ]);
            }

            if (UserBalance::where('userid',$data['userid'])->exists()) {
                $userblance = UserBalance::where('userid',$data['userid'])->first();
                if ($data['senderidtype'] == 'mask') {
                    $userblance->mask += ($data['messagecount']*count($totalsms));
                }

                if ($data['senderidtype'] == 'nomask') {
                    $userblance->nonmask += ($data['messagecount']*count($totalsms));
                }

                if ($data['senderidtype'] == 'voice') {
                    $userblance->voice += ($data['messagecount']*count($totalsms));
                }
                $userblance->balance_date = Carbon::today();
                $userblance->save();
            } else {

                $currentdate = Carbon::today();

                $balusers = User::whereIn('id', function($query){
                    $query->select('user_id')
                    ->from('user_sent_smses');
                })->get();

                $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();

                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
                    && !$checkArchive->isEmpty()
                ) {
                    foreach($balusers as $user) {
                        $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                from archive_sent_smses 
                                                                where sms_catagory='mask'
                                                                and status = true
                                                                and user_id = $user->id) as 'mask',
        
                                                        (select sum(number_of_sms) 
                                                                from archive_sent_smses 
                                                                where sms_catagory='nomask'
                                                                and status = true
                                                                and user_id = $user->id) as 'nonmask',
        
                                                        (select sum(number_of_sms) 
                                                                from archive_sent_smses 
                                                                where sms_catagory='voice'
                                                                and status = true
                                                                and user_id = $user->id) as 'voice',
                                                        DATE(submitted_at) balance_date
        
                                                        FROM `user_sent_smses`
                                                        where status = true
                                                        and user_id = $user->id
                                    "));
        
                        $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
        
                        if ($checkbalance->isEmpty()) { 
                            DB::table("user_balance")->insert([
                                'userid' => $user->id,
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        } else {
                            
                            DB::table("user_balance")->where('userid',$user->id)->update([
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        }
                    }

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {

                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);

                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }

                } else {
                
                    foreach($balusers as $user) {
                        $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                from user_sent_smses 
                                                                where sms_catagory='mask'
                                                                and status = true
                                                                and user_id = $user->id) as 'mask',
        
                                                        (select sum(number_of_sms) 
                                                                from user_sent_smses 
                                                                where sms_catagory='nomask'
                                                                and status = true
                                                                and user_id = $user->id) as 'nonmask',
        
                                                        (select sum(number_of_sms) 
                                                                from user_sent_smses 
                                                                where sms_catagory='voice'
                                                                and status = true
                                                                and user_id = $user->id) as 'voice',
                                                        DATE(submitted_at) balance_date
        
                                                        FROM `user_sent_smses`
                                                        where status = true
                                                        and user_id = $user->id
                                    "));
        
                        $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
        
                        if ($checkbalance->isEmpty()) { 
                            DB::table("user_balance")->insert([
                                'userid' => $user->id,
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        } else {
                            
                            DB::table("user_balance")->where('userid',$user->id)->update([
                                'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                'balance_date' => $currentdate
                            ]);
                        }
                    }

                    $backupstage = DB::table("backups_stage")->get();

                    if ($backupstage[0]->status) {

                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);

                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }

                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }
                }
            }

            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
            ) {

                $backupstage = DB::table("backups_stage")->get();

                if ($backupstage[0]->status) {

                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
                    ) {
                        $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                        $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                        $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                        
                        $userbalance = UserBalance::where('userid',$data['userid'])->first();
                        $userbalance->mask += $currentmasksms;
                        $userbalance->nonmask += $currentnonmasksms;
                        $userbalance->voice += $currentvoicesms;
                        $userbalance->save();
                    }

                    CheckRuntimeBalance::where('userid', $data['userid'])->update([
                        'ischecked' => true
                    ]);

                } else {
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                    ->where('ischecked',false)->exists()
                    ) {
                        $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                        $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                        $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                        
                        $userbalance = UserBalance::where('userid',$data['userid'])->first();
                        $userbalance->mask += $currentmasksms;
                        $userbalance->nonmask += $currentnonmasksms;
                        $userbalance->voice += $currentvoicesms;
                        $userbalance->save();
                    }

                    CheckRuntimeBalance::where('userid', $data['userid'])->update([
                        'ischecked' => true
                    ]);
                }

            }
                  
            //Need to start later
            /*if ($data['userid'] == 32) {
                $client = new Client([
                    'verify' => false
                ]);

                foreach(explode(",",$data['msisdn']) as $record) {
                    $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                        'query' => [ 
                            'MessageId' => $camid,//$resarr['MessageId'],
                            'MessageStatus' => 'Delivered',
                            'MobileNumber' => $record,
                            'SenderId' => $data['sender'],
                            'ErrorCode' => 0,
                            'Message' => $data['message'],
                            'DoneDate' => $date->addSecond(2)
                        ]
                    ]);
                }
            }*/

            if ($data['userid'] == 98) {
                session()->put('multisms','yes');
                session()->put('errorflug','success');
                session()->put('sender',$data['sender']);
                session()->put('destination',explode(",",$data['msisdn']));
                session()->put('messageId',$camid);
                
            }

            session()->put('sendsuccess',"Sms sent successfully");
            
            return response()->json(['msg' => @$resultArr[0]." message successfully send"], 200);
        }
    }

    public function smsSendToBanglaPhone(array $data){
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');

        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $messType = ["text"=>1 , "flash"=>2 , "unicode"=>3]; 

        $totalsms = explode(",",$data['msisdn']);

        $msgStr =	urlencode($data['message']);
    
        $post_values = [ 
            "userId"		=> $data['username'],               
            "password"		=> $data['password'],  
            "commaSeperatedReceiverNumbers"	    => $data['msisdn'],
            "smsText"		=> $msgStr,
        ];

        // $client = new Client();

        // $clientresponse = $client->request('GET',$data['post_url'],[
        //     'query' => [ 
        //         'userId' => $data['username'],
        //         'password' => $data['password'],
        //         'smsText' => urlencode($data['message']),
        //         'commaSeperatedReceiverNumbers' => $data['msisdn']
        //     ]
        // ]);

        // return $clientresponse->getBody();

            $SendAPI = $data['post_url']."?commaSeperatedReceiverNumbers=".$data['msisdn']."&userId=".$data['username']."&password=".$data['password']."&smsText=".urlencode($data['message']); 
			  
            $output = file($SendAPI); 
            $result_sms = end($output);   //Success Count : 1 and Fail Count : 0
            
            
            
            $resultArr = json_decode($result_sms,true);

            
            
            if($resultArr['isError']!=false )
            { 
                $smssend = 'error';
                $date = \Carbon\Carbon::now();
                DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'Banglaphone',
                    'senderid' => $data['sender'],
                    'error_description' => json_encode(@$resultArr),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                if ($data['userid'] == 98) {
                    session()->put('multisms','yes');
                    session()->put('errorflug','error');
                    session()->put('sender',$data['sender']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                session()->put('senderr',"Sms send error, ".$resultArr['message']);
                session()->forget('sendsuccess');
            } else {
                $totalsms = explode(",",$data['msisdn']);
                $date = \Carbon\Carbon::now();
                /*$smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += ($data['messagecount']*count($totalsms));
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();
                */

                $backupstage = DB::table("backups_stage")->get();

                if ($backupstage[0]->status) {

                    $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->whereIn('to_number',explode(",",$data['msisdn']))
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);
                } else {
                    
                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->whereIn('to_number',explode(",",$data['msisdn']))
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);
                }

                if (UserBalance::where('userid',$data['userid'])->exists()) {
                    $userblance = UserBalance::where('userid',$data['userid'])->first();
                    if ($data['senderidtype'] == 'mask') {
                        $userblance->mask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'nomask') {
                        $userblance->nonmask += ($data['messagecount']*count($totalsms));
                    }
    
                    if ($data['senderidtype'] == 'voice') {
                        $userblance->voice += ($data['messagecount']*count($totalsms));
                    }
                    $userblance->balance_date = Carbon::today();
                    $userblance->save();
                } else {
    
                    $currentdate = Carbon::today();
    
                    $balusers = User::whereIn('id', function($query){
                        $query->select('user_id')
                        ->from('user_sent_smses');
                    })->get();
    
                    $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
    
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        && !$checkArchive->isEmpty()
                    ) {
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
    
                    } else {
                    
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
                    }
                }
    
                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                ) {
    
                    $backupstage = DB::table("backups_stage")->get();
    
                    if ($backupstage[0]->status) {
    
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
    
                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }
    
                }

                //Need to start later
                /*if ($data['userid'] == 32) {

                    $client = new Client([
                        'verify' => false
                    ]);

                    foreach(explode(",",$data['msisdn']) as $record) {
                        $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                            'query' => [ 
                                'MessageId' => $camid,//$resarr['MessageId'],
                                'MessageStatus' => 'Delivered',
                                'MobileNumber' => $record,
                                'SenderId' => $data['sender'],
                                'ErrorCode' => 0,
                                'Message' => $msgStr,
                                'DoneDate' => $date->addSecond(2)
                            ]
                        ]);
                    }
                }*/

                if ($data['userid'] == 98) {
                    session()->put('multisms','yes');
                    session()->put('errorflug','success');
                    session()->put('sender',$data['sender']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                session()->put('sendsuccess','Sms sent successfully');
                session()->forget('senderr');
                return response()->json(['msg' => @$resultArr['isError'].",".@$resultArr['message']],200);
            }
    }

    public function __toString()
    {

    }

    public function smsSendToRanksTel(array $data)
    {
        session()->forget('multisms');
        session()->forget('errorflug');
        session()->forget('sender');
        session()->forget('destination');
        session()->forget('messageId');

        $post_values = [];

        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $camid = substr($data['campaing'],9, strlen($data['campaing']));
        $date = \Carbon\Carbon::now();

        $messType = ["text"=>"ASCII" , "flash"=>"UTF-8" , "unicode"=>"UTF-8"];

        $totalsms = explode(",",$data['msisdn']);

        $gsmnumber = [];

        

        //return $data['msisdn'];

        /*if( $data['messagetype'] == 'unicode'){
					
            $post_values = array( 
                "GSM"=> $data['msisdn'],
                "sender" => $data['sender'],
                "datacoding" => 8,
                "type" =>"longSMS",
                "SMSText"=> utf8_encode($data['message'])
                ); 
        
        }else{ 
            $post_values = array( 
                "GSM"=> $data['msisdn'],
                "sender" => $data['sender'], 
                "type" =>"longSMS",
                "SMSText"=> utf8_encode($data['message'])
            );
        }*/
        $authorization = base64_encode($data['credential']);
        //$authorization = base64_encode('datahostit:$AxnSxn$2018');
        

        if( $data['messagetype'] == 'unicode'){
            foreach($totalsms as $number) {
                array_push($gsmnumber, [
                    'gsm' => $number
                ]);
            }
                $post_values=array(
                    'authentication'=>array('username'=>$data['username'],'password'=>$data['password']),
                    'messages'=>array(
                    array('text'=>$data['message'], 'sender'=>$data['sender'],'datacoding'=>'8',
            
                    'recipients'=>$gsmnumber
                    )
                    )
                    );

            
                    
            
        } else {
            foreach($totalsms as $number) {
                array_push($gsmnumber, [
                    'gsm' => $number
                ]);
            }
            $post_values=array(
                'authentication'=>array('username'=>$data['username'],'password'=>$data['password']),
                'messages'=>array(
                array('text'=>$data['message'], 'sender'=>$data['sender'],
        
                'recipients'=> $gsmnumber
                )
                )
                );
        }

        $jsondataencode=json_encode($post_values);
        $request = curl_init($data['post_url']);
        //curl_setopt($request,CURLOPT_POST,1);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request,CURLOPT_POSTFIELDS,$jsondataencode);
        curl_setopt($request,CURLOPT_HTTPHEADER,array('content-type:application/json'));
        curl_setopt( $request, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
        $result=curl_exec($request);
        curl_close($request); 
        $decodearr = json_decode($result, true);
        
        

        foreach($decodearr['results'] as $status) {
            
            

            if ($status['status'] == 0){
                $smssend = ''; 
                $submitted_id= $status['messageid'];

                $date = \Carbon\Carbon::now();

                

                /*$smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += ($data['messagecount']*count(explode(",",$status['destination'])));
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();
                */

                $backupstage = DB::table("backups_stage")->get();

                if ($backupstage[0]->status) {

                    $sentsms = UserSentSmsBackup::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->where('to_number',$status['destination'])
                                        ->where('status', false)
                                        ->update([
                                            'status' => true
                                        ]);
                } else {
                    
                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->where('to_number',$status['destination'])
                                        ->where('status', false)
                                        ->update([
                                            'status' => true
                                        ]);
                }

                if (UserBalance::where('userid',$data['userid'])->exists()) {
                    $userblance = UserBalance::where('userid',$data['userid'])->first();
                    if ($data['senderidtype'] == 'mask') {
                        $userblance->mask += ($data['messagecount']*count(explode(",",$status['destination'])));
                    }
    
                    if ($data['senderidtype'] == 'nomask') {
                        $userblance->nonmask += ($data['messagecount']*count(explode(",",$status['destination'])));
                    }
    
                    if ($data['senderidtype'] == 'voice') {
                        $userblance->voice += ($data['messagecount']*count(explode(",",$status['destination'])));
                    }
                    $userblance->balance_date = Carbon::today();
                    $userblance->save();
                } else {
    
                    $currentdate = Carbon::today();
    
                    $balusers = User::whereIn('id', function($query){
                        $query->select('user_id')
                        ->from('user_sent_smses');
                    })->get();
    
                    $checkArchive = DB::table('archive_sent_smses')->where('user_id',$data['userid'])->get();
    
                    if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        && !$checkArchive->isEmpty()
                    ) {
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from archive_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
    
                    } else {
                    
                        foreach($balusers as $user) {
                            $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='mask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'mask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='nomask'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'nonmask',
            
                                                            (select sum(number_of_sms) 
                                                                    from user_sent_smses 
                                                                    where sms_catagory='voice'
                                                                    and status = true
                                                                    and user_id = $user->id) as 'voice',
                                                            DATE(submitted_at) balance_date
            
                                                            FROM `user_sent_smses`
                                                            where status = true
                                                            and user_id = $user->id
                                        "));
            
                            $checkbalance = DB::table("user_balance")->where('userid',$user->id)->get();
            
                            if ($checkbalance->isEmpty()) { 
                                DB::table("user_balance")->insert([
                                    'userid' => $user->id,
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            } else {
                                
                                DB::table("user_balance")->where('userid',$user->id)->update([
                                    'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                                    'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                                    'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                                    'balance_date' => $currentdate
                                ]);
                            }
                        }
    
                        $backupstage = DB::table("backups_stage")->get();
    
                        if ($backupstage[0]->status) {
    
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
    
                        } else {
                            if (CheckRuntimeBalance::where('userid', $data['userid'])
                                            ->where('ischecked',false)->exists()
                            ) {
                                $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                                $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                                $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                                
                                $userbalance = UserBalance::where('userid',$data['userid'])->first();
                                $userbalance->mask += $currentmasksms;
                                $userbalance->nonmask += $currentnonmasksms;
                                $userbalance->voice += $currentvoicesms;
                                $userbalance->save();
                            }
    
                            CheckRuntimeBalance::where('userid', $data['userid'])->update([
                                'ischecked' => true
                            ]);
                        }
                    }
                }
    
                if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                ) {
    
                    $backupstage = DB::table("backups_stage")->get();
    
                    if ($backupstage[0]->status) {
    
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSmsBackup::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
    
                    } else {
                        if (CheckRuntimeBalance::where('userid', $data['userid'])
                                        ->where('ischecked',false)->exists()
                        ) {
                            $currentmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                            $currentnonmasksms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                            $currentvoicesms = UserSentSms::where('user_id',$data['userid'])->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                            
                            $userbalance = UserBalance::where('userid',$data['userid'])->first();
                            $userbalance->mask += $currentmasksms;
                            $userbalance->nonmask += $currentnonmasksms;
                            $userbalance->voice += $currentvoicesms;
                            $userbalance->save();
                        }
    
                        CheckRuntimeBalance::where('userid', $data['userid'])->update([
                            'ischecked' => true
                        ]);
                    }
    
                }
                
                //Need to start later
                /*if ($data['userid'] == 32) {
                    $client = new Client([
                        'verify' => false
                    ]);

                    foreach(explode(",",$data['msisdn']) as $record) {
                        $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                            'query' => [ 
                                'MessageId' => $camid,//$resarr['MessageId'],
                                'MessageStatus' => 'Delivered',
                                'MobileNumber' => $record,
                                'SenderId' => $data['sender'],
                                'ErrorCode' => 0,
                                'Message' => $data['message'],
                                'DoneDate' => $date->addSecond(2)
                            ]
                        ]);
                    }
                }*/

                if ($data['userid'] == 98) {

                    session()->put('multisms','yes');
                    session()->put('errorflug','success');
                    session()->put('sender',$data['sender']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }

                session()->put('sendsuccess','Sms sent successfully');

                return response()->json(['msg' => $status['messageid'] || $status['messageid']],200); 
                
            } else {
                $date = \Carbon\Carbon::now();
                DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'rankstel',
                    'senderid' => $data['sender'],
                    'error_description' => json_encode($decodearr['results']),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                if ($data['userid'] == 98) {
                    session()->put('multisms','yes');
                    session()->put('errorflug','error');
                    session()->put('sender',$data['sender']);
                    session()->put('destination',explode(",",$data['msisdn']));
                    session()->put('messageId',$camid);
                    
                }
                session()->put('senderr',"status ".$status['status']." destination ".$status['destination']." error,please contact to vendor");
            }
        }

        
        /*$post_string = "";
        foreach( $post_values as $key => $pvalue )
            { $post_string .= "$key=" . urlencode( $pvalue ) . "&"; }
        $post_string = rtrim( $post_string, "& " );

        $request = curl_init($data['post_url']);  
        curl_setopt($request, CURLOPT_HEADER, 0);  
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);  
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
        $post_response = curl_exec($request);  
        curl_close($request); 
	

        $xml_object=simplexml_load_string($post_response);
        $xml_array=$xml_array=json_decode(json_encode($xml_object),true);

        //return $xml_array;

        if(@$xml_array['result']['status']!=0 || @$xml_array['result'][0]['status']!=0)
        { 
            
            
            DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'rankstel',
                    'error_description' => json_encode($xml_array['result']),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            session()->put('senderr',"status ".@$xml_array['result']['status'].@$xml_array['result'][0]['status']." destination ".@$xml_array['result']['destination'].@$xml_array['result'][0]['destination']." error,please contact to vendor");
        } else { 
            $totalsms = explode(",",$data['msisdn']);
            $smssend = ''; 
            $submitted_id= @$xml_array['result']['messageid'] || $xml_array['result'][0]['messageid'];

            $date = \Carbon\Carbon::now();
            $smscount = UserCountSms::firstOrNew([
                'campaing_name' => $data['campaing'],
                'sms_category' => $data['senderidtype']
            ]);
            $smscount->user_id = $data['userid'];
            $smscount->sms_count += ($data['messagecount']*count($totalsms));
            $smscount->month_name =  $date->format('F');
            $smscount->year_name = $date->format('Y');
            $smscount->owner_id = $data['owner_id'];
            $smscount->owner_type = $data['owner_type'];
            $smscount->save();

            $sentsms = UserSentSms::where('user_id', $data['userid'])
                                    ->where('remarks',$data['campaing'])
                                    ->whereIn('to_number',explode(",",$data['msisdn']))
                                    ->where('status', false)
                                    ->update([
                                        'status' => true
                                    ]);

            if ($data['userid'] == 32) {
                $client = new Client([
                    'verify' => false
                ]);

                foreach(explode(",",$data['msisdn']) as $record) {
                    $clientresponse = $client->request('GET','http://164.52.193.173:6005/api/DLR',[
                        'query' => [ 
                            'MessageId' => $camid,//$resarr['MessageId'],
                            'MessageStatus' => 'Delivered',
                            'MobileNumber' => $record,
                            'SenderId' => $data['sender'],
                            'ErrorCode' => 0,
                            'Message' => $data['message'],
                            'DoneDate' => $date
                        ]
                    ]);
                }
            }

            session()->put('sendsuccess','Sms sent successfully');

            return response()->json(['msg' => @$xml_array['result']['messageid'] || $xml_array['result'][0]['messageid']],200); 
        }*/
    }


    /**
     * Manage bulk SMS
     *
     * @return void
     */
    public function manageBulkSms()
    {
        // DB table to use
        $table = 'manage_bulk_pending_sms';

        // Table's primary key
        $primaryKey = 'id';

        $where = " user_id = '".Auth::guard('web')->user()->id."'";

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes

        
        $columns = array(
            //array( 'db' => 'id', 'dt' => 0 ),
            array( 'db' => 'remarks', 'dt' => 0 ),
            array( 'db' => 'sms_type',  'dt' => 1 ),
            array( 'db' => 'sms_catagory',   'dt' => 2 ),
            array( 'db' => 'sms_content',   'dt' => 3 ),
            array( 'db' => 'number_of_sms',   'dt' => 4 ),
            array( 'db' => 'total_contacts',   'dt' => 5 ),
            array( 'db' => 'name',   'dt' => 6 ),
            array( 'db' => 'sender_name',   'dt' => 7 ),
            array( 'db' => 'status',   'dt' => 8 ),
            array( 'db' => 'created_at',   'dt' => 9 ),
            array( 'db' => 'user_id',   'dt' => 10 ),
            array( 'db' => 'user_sender_id',   'dt' => 11 )
            
        );

        

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
    }
    
    
}