<?php

namespace App\Core\UserCountSms;

use App\Core\UserCountSms\UserCountSms;
use App\UserCountSms as AppUserCountSms;
use App\ProductSale;
use App\UserSentSms;
use App\ArchiveSentSms;
use App\UserBalance;
use App\UserSentSmsBackup;
use Carbon\Carbon;

class UserCountSmsDetails implements UserCountSms
{

    /**
     * Get total consume mask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function totalConsumeMaskBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        /*$maskbal = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->sum('number_of_sms');
        */

        $maskusrbal = 0;
        $balance = UserBalance::where('userid',$userid)->get();

        if (!$balance->isEmpty()) {
            
            $maskarchivebal = UserBalance::where('userid',$userid)->first();

            $maskusrbal = $maskarchivebal->mask;
        }

         /*                           ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->sum('number_of_sms');*/

        /*$maskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','mask')
                                    ->sum('sms_count');
        */

        return $maskusrbal;
        //return ($maskbal+$backupmaskbal+$maskusrbal);
    }

    /**
     * Get total consume mask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function resellerTotalConsumeMaskBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $maskbal = ProductSale::where('invoice_owner_id',$userid)
                                    ->where('sms_category','mask')
                                    ->where('invoice_owner_type','reseller')
                                    ->sum('qty');

        return $maskbal;
    }

    /**
     * Get total consume nonmask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function totalConsumeNonMaskBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        /*$nonmaskbal = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        $backupnonmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->sum('number_of_sms');
        */

        $nonmaskusrbal = 0;
        $balance = UserBalance::where('userid',$userid)->get();

        if (!$balance->isEmpty()) {
            
            $nonmaskarchivebal = UserBalance::where('userid',$userid)->first();

            $nonmaskusrbal = $nonmaskarchivebal->nonmask;
        }
        
        /*$nonmaskarchivebal = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        $nonmaskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','nomask')
                                    ->sum('sms_count');
                                    */

        return $nonmaskusrbal;
        //return ($nonmaskbal+$backupnonmaskbal+$nonmaskusrbal);
    }

    /**
     * Get total consume nonmask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function resellerTotalConsumeNonMaskBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $nonmaskbal = ProductSale::where('invoice_owner_id',$userid)
                                    ->where('sms_category','nomask')
                                    ->where('invoice_owner_type','reseller')
                                    ->sum('qty');

        return $nonmaskbal;
    }


    /**
     * Get total consume voice sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function totalConsumeVoiceBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        /*$voicebal = UserSentSms::where('user_id',$userid)
                                    ->where('status',true)
                                    ->where('sms_catagory','voice')
                                    ->sum('number_of_sms');
        
        $backupvoicebal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('status',true)
                                    ->where('sms_catagory','voice')
                                    ->sum('number_of_sms');
        */
        $voiceusrbal = 0;
        $balance = UserBalance::where('userid',$userid)->get();

        if (!$balance->isEmpty()) {
            
            $voicearchivebal = UserBalance::where('userid',$userid)->first();

            $voiceusrbal = $voicearchivebal->voice;
        }
        
        /*$voicearchivebal = ArchiveSentSms::where('user_id',$userid)
                                    ->where('status',true)
                                    ->where('sms_catagory','voice')
                                    ->sum('number_of_sms');

        $voicebal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','voice')
                                    ->sum('sms_count');
                                    */

        return $voiceusrbal;
        //return ($voicebal+$backupvoicebal+$voiceusrbal);
    }

    /**
     * Get total consume voice sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function resellerTotalConsumeVoiceBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $voicebal = ProductSale::where('invoice_owner_id',$userid)
                                    ->where('sms_category','voice')
                                    ->where('invoice_owner_type','reseller')
                                    ->sum('qty');

        return $voicebal;
    }

    /**
     * Get today's consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientConsumeMaskSmsBalance($userid)
    {

        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $maskbal = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        

        $maskarchivebal = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        /*$maskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','mask')
                                    ->whereDate('created_at', Carbon::today())
                                    ->sum('sms_count');
                                    */

        return ($maskbal+$backupmaskbal+$maskarchivebal);
    }

    /**
     * Get today's consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientConsumeMaskSmsBalance($userid)
    {

        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $maskbal = UserSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        $maskarchivebal = ArchiveSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        /*$maskbal = AppUserCountSms::where('owner_id',$userid)
                                    ->where('sms_category','mask')
                                    ->whereDate('created_at', Carbon::today())
                                    ->where('owner_type','reseller')
                                    ->sum('sms_count');
                                    */

        return ($maskbal+$backupmaskbal+$maskarchivebal);
    }

    /**
     * Get today's consume nonmask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientConsumeNonMaskSmsBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $nonmaskbal = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        $backupnonmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        $nonmaskarchivebal = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        /*$nonmaskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','nomask')
                                    ->whereDate('created_at', Carbon::today())
                                    ->sum('sms_count');
                                    */

        return ($nonmaskbal+$backupnonmaskbal+$nonmaskarchivebal);
    }

    /**
     * Get today's consume nonmask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientConsumeNonMaskSmsBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $nonmaskbal = UserSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        $backupnonmaskbal = UserSentSmsBackup::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        $nonmaskarchivebal = ArchiveSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        /*$nonmaskbal = AppUserCountSms::where('owner_id',$userid)
                                    ->where('sms_category','nomask')
                                    ->whereDate('created_at', Carbon::today())
                                    ->where('owner_type','reseller')
                                    ->sum('sms_count');
        */

        return ($nonmaskbal+$backupnonmaskbal+$nonmaskarchivebal);
    }

    /**
     * Get today's consume voice sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientConsumeVoiceSmsBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $voicebal = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        $backupvoicebal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        $voicearchivebal = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereDate('submitted_at', Carbon::today())
                                    ->sum('number_of_sms');

        /*$voicebal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','voice')
                                    ->whereDate('created_at', Carbon::today())
                                    ->sum('sms_count');
                                    */

        return ($voicebal+$backupvoicebal+$voicearchivebal);
    }


    /**
     * Get today's consume voice sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientConsumeVoiceSmsBalance($userid)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $voicebal = UserSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','voice')
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        $backupvoicebal = UserSentSmsBackup::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','voice')
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        $voicearchivebal = ArchiveSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                    })
                                    ->where('sms_catagory','voice')
                                    ->whereDate('submitted_at', Carbon::today())
                                    //->where('owner_type','reseller')
                                    ->sum('number_of_sms');

        /*$voicebal = AppUserCountSms::where('owner_id',$userid)
                                    ->where('sms_category','voice')
                                    ->whereDate('created_at', Carbon::today())
                                    ->where('owner_type','reseller')
                                    ->sum('sms_count');
                                    */

        return ($voicebal+$backupvoicebal+$voicearchivebal);
    }


    /**
     * Get monthly consume mask sms balance
     *
     * @param int $userid
     * @param string $monthname
     * @return void
     */
    public function monthlyConsumeMaskSmsBalance($userid, $monthname = null)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $date = Carbon::now();

        $currentmonth = $date->month;
        //$currentmonth = $date->format("F");

        if (empty($monthname))
        {
            $monthname = $currentmonth;
        }

        $maskbal = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');

        $maskarchivebal = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');

        /*$maskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','mask')
                                    ->where('month_name', $monthname)
                                    ->sum('sms_count');
                                    */

        return ($maskbal+$backupmaskbal+$maskarchivebal);
    }

    /**
     * Get monthly consume nonmask sms balance
     *
     * @param int $userid
     * @param string $monthname
     * @return void
     */
    public function monthlyConsumeNonMaskSmsBalance($userid, $monthname = null)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $date = Carbon::now();

        $currentmonth = $date->month;
        //$currentmonth = $date->format("F");

        if (empty($monthname))
        {
            $monthname = $currentmonth;
        }

        $nonmaskbal = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');

        $backupnonmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');

        $nonmaskarchivebal = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');
        /*$nonmaskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','mask')
                                    ->where('month_name', $monthname)
                                    ->sum('sms_count');
                                    */

        return ($nonmaskbal+$backupnonmaskbal+$nonmaskarchivebal);
    }

    /**
     * Get monthly consume voice sms balance
     *
     * @param int $userid
     * @param string $monthname
     * @return void
     */
    public function monthlyConsumeVoiceSmsBalance($userid, $monthname=null)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $date = Carbon::now();

        $currentmonth = $date->month;
        //$currentmonth = $date->format("F");

        if (empty($monthname))
        {
            $monthname = $currentmonth;
        }

        $voice = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');

        $backupvoice = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');
        $archivevoice = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', $monthname)
                                    ->sum('number_of_sms');

        /*$voice = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','voice')
                                    ->where('month_name', $monthname)
                                    ->sum('sms_count');
                                    */

        return ($voice+$backupvoice+$archivevoice);
    }

    /**
     * Get yearly consume mask sms balance
     *
     * @param int $userid
     * @param int $yearly
     * @return void
     */
    public function yearlyConsumeMaskSmsBalance($userid, $yearly = null)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $date = Carbon::now();

        $currentyear = $date->year;
        //$currentyear = $date->format("Y");

        if (empty($yearname))
        {
            $yearname = $currentyear;
        }

        $mask = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        $backupmask = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        $archivemask = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        /*$maskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','mask')
                                    ->where('year_name', $yearname)
                                    ->sum('sms_count');
                                    */

        return ($mask+$backupmask+$archivemask);
    }

    /**
     * Get yearly consume nonmask sms balance
     *
     * @param int $userid
     * @param int $yearly
     * @return void
     */
    public function yearlyConsumeNonMaskSmsBalance($userid, $yearname = null)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $date = Carbon::now();

        $currentyear = $date->year;
        //$currentyear = $date->format("Y");

        if (empty($yearname))
        {
            $yearname = $currentyear;
        }

        $nomask = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        $backupnomask = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        $archivenomask = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','nomask')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        /*$maskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','nomask')
                                    ->where('year_name', $yearname)
                                    ->sum('sms_count');
                                    */

        return ($nomask+$backupnomask+$archivenomask);
    }

    /**
     * Get yearly consume voice sms balance
     *
     * @param int $userid
     * @param int $yearly
     * @return void
     */
    public function yearlyConsumeVoiceSmsBalance($userid, $yearname=null)
    {
        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $date = Carbon::now();

        $currentyear = $date->year;
        //$currentyear = $date->format("Y");

        if (empty($yearname))
        {
            $yearname = $currentyear;
        }

        $voice = UserSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        $backupvoice = UserSentSmsBackup::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        $archivevoice = ArchiveSentSms::where('user_id',$userid)
                                    ->where('sms_catagory','voice')
                                    ->where('status',true)
                                    ->whereYear('submitted_at', $yearname)
                                    ->sum('number_of_sms');

        /*$maskbal = AppUserCountSms::where('user_id',$userid)
                                    ->where('sms_category','voice')
                                    ->where('year_name', $yearname)
                                    ->sum('sms_count');
                                    */

        return ($voice+$backupvoice+$archivevoice);
    }

     /**
     * Get this week consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientThisWeekConsumeMaskSmsBalance($userid)
    {

        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);

        $maskbal = UserSentSms::where('user_id',$userid)
                                    //->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereBetween('submitted_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                    ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    //->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereBetween('submitted_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                    ->sum('number_of_sms');

        $maskarchivebal = ArchiveSentSms::where('user_id',$userid)
                                    //->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereBetween('submitted_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                    ->sum('number_of_sms');

        return ($maskbal+$backupmaskbal+$maskarchivebal);
    }


    /**
     * Get this week consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientThisWeekConsumeMaskSmsBalance($userid)
    {

        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);

        $maskbal = UserSentSms::whereIn('user_id',function($query) use($userid){
                                            $query->select('id')
                                            ->from('users')
                                            ->where('reseller_id',$userid);
                                    })
                                    ->whereBetween('submitted_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    //->where('owner_type', 'reseller')
                                    ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::whereIn('user_id',function($query) use($userid){
                                            $query->select('id')
                                            ->from('users')
                                            ->where('reseller_id',$userid);
                                    })
                                    ->whereBetween('submitted_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    //->where('owner_type', 'reseller')
                                    ->sum('number_of_sms');

        $maskarchivebal = ArchiveSentSms::whereIn('user_id',function($query) use($userid){
                                            $query->select('id')
                                            ->from('users')
                                            ->where('reseller_id',$userid);
                                    })
                                    ->whereBetween('submitted_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                    ->where('sms_catagory','mask')
                                    ->where('status',true)
                                    //->where('owner_type', 'reseller')
                                    ->sum('number_of_sms');

        return ($maskbal+$backupmaskbal+$maskarchivebal);
    }

    /**
     * Get this month consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientThisMonthConsumeMaskSmsBalance($userid)
    {

        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $maskbal = UserSentSms::where('user_id',$userid)
                                    //->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', Carbon::now()->month)
                                    ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::where('user_id',$userid)
                                    //->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', Carbon::now()->month)
                                    ->sum('number_of_sms');
                                    
        $maskarchivebal = ArchiveSentSms::where('user_id',$userid)
                                    //->where('sms_catagory','mask')
                                    ->where('status',true)
                                    ->whereMonth('submitted_at', Carbon::now()->month)
                                    ->sum('number_of_sms');

        return ($maskbal+$backupmaskbal+$maskarchivebal);
    }

    /**
     * Get this month consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientThisMonthConsumeMaskSmsBalance($userid)
    {

        if (! isset($userid))
        {
            return response()->json(['msg' => 'User id can\'t be null'], 406);
        }

        $maskbal = UserSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                })
                                ->whereMonth('submitted_at', Carbon::now()->month)
                                ->where('sms_catagory','mask')
                                ->where('status',true)
                                //->where('owner_type', 'reseller')
                                ->sum('number_of_sms');

        $backupmaskbal = UserSentSmsBackup::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                })
                                ->whereMonth('submitted_at', Carbon::now()->month)
                                ->where('sms_catagory','mask')
                                ->where('status',true)
                                //->where('owner_type', 'reseller')
                                ->sum('number_of_sms');

        $maskarchivebal = ArchiveSentSms::whereIn('user_id',function($query) use($userid){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$userid);
                                })
                                ->whereMonth('submitted_at', Carbon::now()->month)
                                ->where('sms_catagory','mask')
                                ->where('status',true)
                                //->where('owner_type', 'reseller')
                                ->sum('number_of_sms');

        /*$maskbal = AppUserCountSms::where('owner_id',$userid)
                                    ->where('month_name', Carbon::now()->format('F'))
                                    ->where('owner_type','reseller')
                                    ->sum('sms_count');
                                    */

        return ($maskbal+$backupmaskbal+$maskarchivebal);
    }

     /**
      * Get total sms sent history in current day
      *
      * @return void
      */
      public function todaysSmsSentHistoryForRoot()
      {
        //$todayssmssent = AppUserCountSms::whereDate('created_at', Carbon::today())
        //->sum('sms_count');
        $todayssmssent = UserSentSms::whereDate('submitted_at', Carbon::today())
        ->where('status',true)
        ->sum('number_of_sms');

        $backuptodayssmssent = UserSentSmsBackup::whereDate('submitted_at', Carbon::today())
        ->where('status',true)
        ->sum('number_of_sms');

        $todaysarchivesmssent = ArchiveSentSms::whereDate('submitted_at', Carbon::today())
        ->where('status',true)
        ->sum('number_of_sms');

        return ($todayssmssent+$backuptodayssmssent+$todaysarchivesmssent);
      }


      /**
      * Get total sms sent history in current day
      *
      * @return void
      */
      public function resellerClientTodaysSmsSentHistoryForRoot($data)
      {

        $todayssmssent = UserSentSms::whereIn('user_id',function($query) use($data){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$data['owner_id']);
                                })
                                ->whereDate('submitted_at', Carbon::now())
                                ->where('status',true)
                                //->where('owner_type', 'reseller')
                                ->sum('number_of_sms');

        $backuptodayssmssent = UserSentSmsBackup::whereIn('user_id',function($query) use($data){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$data['owner_id']);
                                })
                                ->whereDate('submitted_at', Carbon::now())
                                ->where('status',true)
                                //->where('owner_type', 'reseller')
                                ->sum('number_of_sms');

        $todaysarchivesmssent = ArchiveSentSms::whereIn('user_id',function($query) use($data){
                                        $query->select('id')
                                        ->from('users')
                                        ->where('reseller_id',$data['owner_id']);
                                })
                                ->whereDate('submitted_at', Carbon::now())
                                ->where('status',true)
                                //->where('owner_type', 'reseller')
                                ->sum('number_of_sms');
                                
        /*$todayssmssent = AppUserCountSms::whereDate('created_at', Carbon::today())
                                        ->where('owner_type','reseller')
                                        ->where('owner_id',$data['owner_id'])
                                        ->sum('sms_count');
                                        */

        return ($todayssmssent+$backuptodayssmssent+$todaysarchivesmssent);
      }


      /**
      * Get total sms sent history in current weak
      *
      * @return void
      */
      public function thisWeekSmsSentHistoryForRoot()
      {

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);

        $thisweakbal = UserSentSms::whereBetween('submitted_at', [
                            Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()
                       ])
                       ->where('status',true)
                       ->sum('number_of_sms');

        $backupthisweakbal = UserSentSmsBackup::whereBetween('submitted_at', [
                            Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()
                       ])
                       ->where('status',true)
                       ->sum('number_of_sms');

        $thisweakarchivebal = ArchiveSentSms::whereBetween('submitted_at', [
                        Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()
                   ])
                   ->where('status',true)
                   ->sum('number_of_sms');

        return ($thisweakbal+$backupthisweakbal+$thisweakarchivebal);
      }

      /**
      * Get total sms sent history in current weak
      *
      * @return void
      */
      public function resellerThisWeekSmsSentHistoryForRoot($data)
      {

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);

        $thisweakbal = UserSentSms::whereBetween('submitted_at', [
                            Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()
                       ])
                       ->whereIn('user_id',function($query) use($data){
                            $query->select('id')
                            ->from('users')
                            ->where('reseller_id',$data['owner_id']);
                        })
                       ->where('status',true)
                       ->sum('number_of_sms');

        $backupthisweakbal = UserSentSmsBackup::whereBetween('submitted_at', [
                            Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()
                       ])
                       ->whereIn('user_id',function($query) use($data){
                            $query->select('id')
                            ->from('users')
                            ->where('reseller_id',$data['owner_id']);
                        })
                       ->where('status',true)
                       ->sum('number_of_sms');

        $thisweakarchivebal = ArchiveSentSms::whereBetween('submitted_at', [
                            Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()
                       ])
                       ->whereIn('user_id',function($query) use($data){
                            $query->select('id')
                            ->from('users')
                            ->where('reseller_id',$data['owner_id']);
                        })
                       ->where('status',true)
                       ->sum('number_of_sms');

        return ($thisweakbal+$backupthisweakbal+$thisweakarchivebal);
      }

      /**
      * Get total sms sent history in current month
      *
      * @return void
      */
      public function thisMonthSmsSentHistoryForRoot()
      {
        $monthsmssent = UserSentSms::whereMonth('submitted_at', Carbon::now()->month)
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        $backupmonthsmssent = UserSentSmsBackup::whereMonth('submitted_at', Carbon::now()->month)
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        $montharchivesmssent = ArchiveSentSms::whereMonth('submitted_at', Carbon::now()->month)
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        return ($monthsmssent+$backupmonthsmssent+$montharchivesmssent);
      }

      /**
      * Get total sms sent history in current month
      *
      * @return void
      */
      public function resellerThisMonthSmsSentHistoryForRoot($data)
      {
        $monthsmssent = UserSentSms::whereMonth('submitted_at', Carbon::now()->month)
                                        ->whereIn('user_id',function($query) use($data){
                                            $query->select('id')
                                            ->from('users')
                                            ->where('reseller_id',$data['owner_id']);
                                        })
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        $backupmonthsmssent = UserSentSmsBackup::whereMonth('submitted_at', Carbon::now()->month)
                                        ->whereIn('user_id',function($query) use($data){
                                            $query->select('id')
                                            ->from('users')
                                            ->where('reseller_id',$data['owner_id']);
                                        })
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        $montharchivesmssent = ArchiveSentSms::whereMonth('submitted_at', Carbon::now()->month)
                                        ->whereIn('user_id',function($query) use($data){
                                            $query->select('id')
                                            ->from('users')
                                            ->where('reseller_id',$data['owner_id']);
                                        })
                                    ->where('status',true)
                                    ->sum('number_of_sms');

        return ($monthsmssent+$backupmonthsmssent+$montharchivesmssent);
      }
}