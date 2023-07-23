<?php

namespace App\Core\BalanceReconciliation;

use App\CheckRuntimeBalance;
use App\Core\BalanceReconciliation\BalanceReconciliation;
use App\User;
use App\UserBalance;
use App\UserSentSms;
use App\UserSentSmsBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceReconciliationDetails implements BalanceReconciliation {

    public function calculateUserBalance($userid)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        
        $currentdate = Carbon::today();
    
       
        $checkArchive = DB::table('archive_sent_smses')->where('user_id',$userid)->get();

        if (!$checkArchive->isEmpty()) {
                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                        from archive_sent_smses 
                                                        where sms_catagory='mask'
                                                        and status = true
                                                        and user_id = $userid) as 'mask',

                                                (select sum(number_of_sms) 
                                                        from archive_sent_smses 
                                                        where sms_catagory='nomask'
                                                        and status = true
                                                        and user_id = $userid) as 'nonmask',

                                                (select sum(number_of_sms) 
                                                        from archive_sent_smses 
                                                        where sms_catagory='voice'
                                                        and status = true
                                                        and user_id = $userid) as 'voice',
                                                DATE(submitted_at) balance_date

                                                FROM `archive_sent_smses`
                                                where status = true
                                                and user_id = $userid
                            "));

                $checkbalance = DB::table("user_balance")->where('userid',$userid)->get();

                if ($checkbalance->isEmpty()) { 
                    DB::table("user_balance")->insert([
                        'userid' => $userid,
                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                        'balance_date' => $currentdate
                    ]);
                } else {
                    
                    DB::table("user_balance")->where('userid',$userid)->update([
                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                        'balance_date' => $currentdate
                    ]);
                }

            $backupstage = DB::table("backups_stage")->get();

            if ($backupstage[0]->status) {

                    $currentmasksms = UserSentSmsBackup::where('user_id',$userid)->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$userid)->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                    $currentvoicesms = UserSentSmsBackup::where('user_id',$userid)->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                    
                    $userbalance = UserBalance::where('userid',$userid)->first();
                    $userbalance->mask += $currentmasksms;
                    $userbalance->nonmask += $currentnonmasksms;
                    $userbalance->voice += $currentvoicesms;
                    $userbalance->save();

            } else {

                    $currentmasksms = UserSentSms::where('user_id',$userid)->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                    $currentnonmasksms = UserSentSms::where('user_id',$userid)->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                    $currentvoicesms = UserSentSms::where('user_id',$userid)->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                    
                    $userbalance = UserBalance::where('userid',$userid)->first();
                    $userbalance->mask += $currentmasksms;
                    $userbalance->nonmask += $currentnonmasksms;
                    $userbalance->voice += $currentvoicesms;
                    $userbalance->save();
            }

        } else {
        
                $smsbalance = DB::select(DB::raw("SELECT count(*),(select sum(number_of_sms) 
                                                        from user_sent_smses 
                                                        where sms_catagory='mask'
                                                        and status = true
                                                        and user_id = $userid) as 'mask',

                                                (select sum(number_of_sms) 
                                                        from user_sent_smses 
                                                        where sms_catagory='nomask'
                                                        and status = true
                                                        and user_id = $userid) as 'nonmask',

                                                (select sum(number_of_sms) 
                                                        from user_sent_smses 
                                                        where sms_catagory='voice'
                                                        and status = true
                                                        and user_id = $userid) as 'voice',
                                                DATE(submitted_at) balance_date

                                                FROM `user_sent_smses`
                                                where status = true
                                                and user_id = $userid
                            "));

                $checkbalance = DB::table("user_balance")->where('userid',$userid)->get();

                if ($checkbalance->isEmpty()) { 
                    DB::table("user_balance")->insert([
                        'userid' => $userid,
                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                        'balance_date' => $currentdate
                    ]);
                } else {
                    
                    DB::table("user_balance")->where('userid',$userid)->update([
                        'mask' => !empty($smsbalance[0]->mask) ? $smsbalance[0]->mask : 0,
                        'nonmask' => !empty($smsbalance[0]->nonmask) ? $smsbalance[0]->nonmask : 0,
                        'voice' => !empty($smsbalance[0]->voice) ? $smsbalance[0]->voice : 0,
                        'balance_date' => $currentdate
                    ]);
                }

            /*$backupstage = DB::table("backups_stage")->get();

            if ($backupstage[0]->status) {

                    $currentmasksms = UserSentSmsBackup::where('user_id',$userid)->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                    $currentnonmasksms = UserSentSmsBackup::where('user_id',$userid)->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                    $currentvoicesms = UserSentSmsBackup::where('user_id',$userid)->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                    
                    $userbalance = UserBalance::where('userid',$userid)->first();
                    $userbalance->mask += $currentmasksms;
                    $userbalance->nonmask += $currentnonmasksms;
                    $userbalance->voice += $currentvoicesms;
                    $userbalance->save();

            } else {
                    $currentmasksms = UserSentSms::where('user_id',$userid)->where('status',true)->where('sms_catagory','mask')->sum('number_of_sms');
                    $currentnonmasksms = UserSentSms::where('user_id',$userid)->where('status',true)->where('sms_catagory','nomask')->sum('number_of_sms');
                    $currentvoicesms = UserSentSms::where('user_id',$userid)->where('status',true)->where('sms_catagory','voice')->sum('number_of_sms');
                    
                    $userbalance = UserBalance::where('userid',$userid)->first();
                    $userbalance->mask += $currentmasksms;
                    $userbalance->nonmask += $currentnonmasksms;
                    $userbalance->voice += $currentvoicesms;
                    $userbalance->save();
            }
            */
        }
    }
}