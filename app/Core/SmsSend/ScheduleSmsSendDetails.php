<?php

namespace App\Core\SmsSend;

use App\Core\ContactsAndGroups\ContactsAndGroups;
use App\Core\Senderid\SenderId;
use App\Core\SmsSend\SmsSend;
use App\Core\Users\ClientInterface;
use App\Datatables\DataTableClass;
use App\UserCountSms;
use App\UserSentSms;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleSmsSendDetails implements SmsSend
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
            //if ($countmsg <= 160) { 
            if ($countmsg <= 153) { 

                return $this->totalmsg = 1;  

            //} else if ($countmsg <= 310) { 
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
        
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $messType = ["text"=>"ASCII" , "flash"=>"UTF-8" , "unicode"=>"UTF-8"];

        $totalsms = explode(",",$data['mobile']);

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
            $date = \Carbon\Carbon::now();
            $smscount = UserCountSms::firstOrNew([
                'campaing_name' => $data['campaing'],
                'sms_category' => $data['senderidtype']
            ]);
            $smscount->user_id = $data['userid'];
            $smscount->sms_count += count($totalsms);
            $smscount->month_name =  $date->format('F');
            $smscount->year_name = $date->format('Y');
            $smscount->owner_id = $data['owner_id'];
            $smscount->owner_type = $data['owner_type'];
            $smscount->save();

            $sentsms = UserSentSms::where('user_id', $data['userid'])
                                    ->where('remarks',$data['campaing'])
                                    ->where('status', false)->update([
                                        'status' => true
                                    ]);
            
            session()->put('sendsuccess','Sms sent successfully');
            session()->forget('senderr');

            return response()->json(['msg' => $resultArr[0].",".$resultArr[1]],200); 
        } else {

            //return $smssend = 'error';
            DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'gp',
                    'error_description' => json_encode(@$resultArr),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                session()->put('senderr',$resultArr[0].",".$resultArr[1]);
                session()->forget('sendsuccess');
        }
    }

    public function smsSendToGp(array $data)
    {
        $messType = ["text"=>1 , "flash"=>2 , "unicode"=>3]; 

        $totalsms = explode(",",$data['msisdn']);

        if($messType[$data['messagetype']]==3)
        {
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
            $submitted_id =  @$resultArr[1];
            
            if($resultArr[1]!=200 )
            { 
                $smssend = 'error';
                DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'gp',
                    'error_description' => json_encode(@$resultArr),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                session()->put('senderr',"Sms send error, ".$submitted_id);
                session()->forget('sendsuccess');
            } else {
                $date = \Carbon\Carbon::now();
                $smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += count($totalsms);
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();

                $sentsms = UserSentSms::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);

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
            $submitted_id =  @$resultArr[1];
            
            if($resultArr[1]!=200 )
            { 
                $smssend = 'error';
                DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'gp',
                    'error_description' => json_encode(@$resultArr),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                session()->put('senderr',"Sms send error, ".$submitted_id);
                session()->forget('sendsuccess');
            } else {
                $date = \Carbon\Carbon::now();
                $smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += count($totalsms);
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();

                $sentsms = UserSentSms::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);

                session()->put('sendsuccess','Sms sent successfully');
                session()->forget('senderr');
                return response()->json(['msg' => @$resultArr[0].",".@$resultArr[1]],200);
            }
        }
    }

    public function smsSendToBlink(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

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

                DB::table('sms_send_errors')
                    ->insert([
                        'operator_type' => 'blink',
                        'error_description' => json_decode(@$resultArr),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);  

                    session()->put('senderr', "Sms send error ".$resultArr[0]." ".$resultArr[1]);
                    session()->forget('sendsuccess');
            } else {
                $date = \Carbon\Carbon::now();
                $smscount = UserCountSms::firstOrNew([
                    'campaing_name' => $data['campaing'],
                    'sms_category' => $data['senderidtype']
                ]);
                $smscount->user_id = $data['userid'];
                $smscount->sms_count += count($totalsms);
                $smscount->month_name =  $date->format('F');
                $smscount->year_name = $date->format('Y');
                $smscount->owner_id = $data['owner_id'];
                $smscount->owner_type = $data['owner_type'];
                $smscount->save();

                $sentsms = UserSentSms::where('user_id', $data['userid'])
                                        ->where('remarks',$data['campaing'])
                                        ->where('status', false)->update([
                                            'status' => true
                                        ]);
                
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
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $SendAPI = $data['post_url']."&To=".$data['To']."&From=".urlencode($data['From'])."&Message=".urlencode($data['Message']); 

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
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel',
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                        
                        session()->put('senderr',@$xml_array['ServiceClass']['ErrorText'].@$xml_array['ServiceClass']['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    
                    $date = \Carbon\Carbon::now();
                    $smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += count($totalsms);
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();

                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    //$sentsms->status = 1;
                    //$sentsms->save();

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            } else {
                if(  @$xml_array['ServiceClass'][0]['ErrorCode']!='0' )
                { 
                    
                    $smssend = 'error'; 
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel',
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                        
                        session()->put('senderr',@$xml_array['ServiceClass'][0]['ErrorText'].@$xml_array['ServiceClass'][0]['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    
                    $date = \Carbon\Carbon::now();
                    $smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += count($totalsms);
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();

                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    //$sentsms->status = 1;
                    //$sentsms->save();

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            }
            
        } catch(\Exception $e) {
            return response()->json(['errmsg' => $e->getMessage()],200);
        }
    }

    public function smsSendToRobiNonMask(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $SendAPI = $data['post_url']."&To=".$data['To']."&From=".urlencode($data['From'])."&Message=".urlencode($data['Message']); 

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
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel',
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                        
                        session()->put('senderr',@$xml_array['ServiceClass']['ErrorText'].@$xml_array['ServiceClass']['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    
                    $date = \Carbon\Carbon::now();
                    $smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += count($totalsms);
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();

                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->where('status', false)->update([
                                                'status' => true
                                            ]);
                    //$sentsms->status = 1;
                    //$sentsms->save();

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            } else {
                if(  @$xml_array['ServiceClass'][0]['ErrorCode']!='0' )
                { 
                    
                    $smssend = 'error'; 
                    DB::table('sms_send_errors')
                        ->insert([
                            'operator_type' => 'Robi/Airtel',
                            'error_description' => json_encode(@$xml_array),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                        
                        session()->put('senderr',@$xml_array['ServiceClass'][0]['ErrorText'].@$xml_array['ServiceClass'][0]['ErrorCode']);
                        session()->forget('sendsuccess');
                } else { 

                    
                    $date = \Carbon\Carbon::now();
                    $smscount = UserCountSms::firstOrNew([
                        'campaing_name' => $data['campaing'],
                        'sms_category' => $data['senderidtype']
                    ]);
                    $smscount->user_id = $data['userid'];
                    $smscount->sms_count += count($totalsms);
                    $smscount->month_name =  $date->format('F');
                    $smscount->year_name = $date->format('Y');
                    $smscount->owner_id = $data['owner_id'];
                    $smscount->owner_type = $data['owner_type'];
                    $smscount->save();

                    $sentsms = UserSentSms::where('user_id', $data['userid'])
                                            ->where('remarks',$data['campaing'])
                                            ->where('status', false)
                                            ->update([
                                                'status' => true
                                            ]);
                    //$sentsms->status = 1;
                    //$sentsms->save();

                    session()->put('sendsuccess','Sms sent successfully');
                    session()->forget('senderr');
                    return response()->json(['msg' => 'Message successfully sent'], 200);
                }
            }
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    public function smsSendToEasyWeb(array $data){

        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $totalsms = explode(",",$data['msisdn']);

        $SendAPI = $data['post_url']."&contacts=".$data['msisdn']."&senderid=".urlencode($data['sender'])."&msg=".urlencode($data['message']); 
			  
        $output = file($SendAPI); 
        $result_sms = end($output);   //Success Count : 1 and Fail Count : 0
        
        
        
        $resultArr = json_decode($result_sms,true); 
          
        if(  $resultArr['code'] == 445080 )
        { 
            $smssend = 'error'; 

            DB::table('sms_send_errors')
                ->insert([
                    'operator_type' => 'EasyWeb',
                    'error_description' => $resultArr['message'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);  

                session()->put('senderr',$resultArr['message']);
        } else {
            $date = \Carbon\Carbon::now();
            $smscount = UserCountSms::firstOrNew([
                'campaing_name' => $data['campaing'],
                'sms_category' => $data['senderidtype']
            ]);
            $smscount->user_id = $data['userid'];
            $smscount->sms_count += count($totalsms);
            $smscount->month_name =  $date->format('F');
            $smscount->year_name = $date->format('Y');
            $smscount->owner_id = $data['owner_id'];
            $smscount->owner_type = $data['owner_type'];
            $smscount->save();

            $sentsms = UserSentSms::where('user_id', $data['userid'])
                                    ->where('remarks',$data['campaing'])
                                    ->where('status', false)
                                    ->update([
                                        'status' => true
                                    ]);

            session()->put('sendsuccess',"Sms sent successfully");
            
            return response()->json(['msg' => @$resultArr[0]." message successfully send"], 200);
        }
    }

    public function __toString()
    {

    }

    public function smsSendToRanksTel(array $data)
    {
        $post_values = [];

        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        $messType = ["text"=>"ASCII" , "flash"=>"UTF-8" , "unicode"=>"UTF-8"];

        $totalsms = explode(",",$data['msisdn']);

        if( $data['messagetype'] == 'unicode'){
					
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
        }

          
        $post_string = "";
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
	

        $xml_object=simplexml_load_string( $post_response); 
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
            $smssend = ''; 
            $submitted_id= @$xml_array['result']['messageid'] || $xml_array['result'][0]['messageid'];

            $date = \Carbon\Carbon::now();
            $smscount = UserCountSms::firstOrNew([
                'campaing_name' => $data['campaing'],
                'sms_category' => $data['senderidtype']
            ]);
            $smscount->user_id = $data['userid'];
            $smscount->sms_count += count($totalsms);
            $smscount->month_name =  $date->format('F');
            $smscount->year_name = $date->format('Y');
            $smscount->owner_id = $data['owner_id'];
            $smscount->owner_type = $data['owner_type'];
            $smscount->save();

            $sentsms = UserSentSms::where('user_id', $data['userid'])
                                    ->where('remarks',$data['campaing'])
                                    ->where('status', false)
                                    ->update([
                                        'status' => true
                                    ]);

            session()->put('sendsuccess','Sms sent successfully');

            return response()->json(['msg' => @$xml_array['result']['messageid'] || $xml_array['result'][0]['messageid']],200); 
        }
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