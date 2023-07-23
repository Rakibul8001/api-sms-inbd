<?php

namespace App\Helper;

use App\Models\Users;
use App\Models\SmsSender;
use App\Models\UserSender;


class SenderIdDetails
{

    public static function smsSendToGp(array $data)
    {
        $messType = ["text"=>1 , "flash"=>2 , "unicode"=>3]; 

        $totalsms = explode(",",$data['msisdn']);

        if($messType[$data['messagetype']]==3)
        {
            $msgStr =  bin2hex(mb_convert_encoding( $data['message'], 'UTF-16'));

            $post_values = [ 
                "username"      => $data['username'],               
                "password"      => $data['password'],  
                "apicode"       => "6", 
                "msisdn"        => $data['msisdn'],
                
                "countrycode"   => "880",
                "messageid"     => 0,
                "cli"           => $data['cli'],     
                "messagetype"   => $messType[$data['messagetype']], 
                "message"       => $msgStr,

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
            $msgStr =   $data['message'];
        
            $post_values = [ 
                "username"      => $data['username'],               
                "password"      => $data['password'],  
                "apicode"       => "6", 
                "msisdn"        => $data['msisdn'],
                
                "countrycode"   => "880",
                "messageid"     => 0,
                "cli"           => $data['cli'],     
                "messagetype"   => $messType[$data['messagetype']], 
                "message"       => $msgStr,
            
                
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
    
}


    