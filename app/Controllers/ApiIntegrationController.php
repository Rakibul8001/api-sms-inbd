<?php 

namespace App\Controllers;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

use App\Models\Users;
use App\Models\Reseller;

use App\Models\Admin\AdminResource;

use App\Models\SmsSender;
use App\Models\SmsSenderTtk;
use App\Models\ProductSale;
use App\Models\AccountTransaction;





use App\Models\ForMigration\UserSentSms;
use App\Models\ForMigration\ArchiveSentSms;
use App\Models\ForMigration\UserSentSmsBackup;
use App\Models\ForMigration\UserBalance;

use App\Models\SmsCampaigns;
use App\Models\SmsCampaignNumbersA;
use App\Models\SmsCampaignNumbersB;
use App\Models\SmsCampaignNumbersC;
use App\Models\SmsCampaignNumbersD;
use App\Models\SmsCampaignNumbersE;
use App\Models\SmsDeliveryReports;
use App\Models\SmsSingle;
use App\Helper\UserBalanceManager;
use Illuminate\Database\Capsule\Manager as DB;

class ApiIntegrationController extends Controller
{

    public function createTtkSenderId($request, $response)
    {
        echo "aborted";
        die();

        $ttksenderIds = SmsSender::where('operator_id', 7)->get();
        
        foreach ($ttksenderIds as $ttkSenderId) {
            
            //get original senderId
            $originalId = SmsSender::where('sender_name', $ttkSenderId->sender_name)->where('id', '!=', $ttkSenderId->id)->first();
            
            $parentSenderId = null;
            if ($originalId) {
                $parentSenderId = $originalId->id;
            } else $parentSenderId = $ttkSenderId->id;

            SmsSenderTtk::create([

                'main_id'     => $parentSenderId,
                'status'      => 1,
                'user'        => $ttkSenderId->user,
                'password'    => $ttkSenderId->password,
                'created_by'  => $ttkSenderId->created_by,
                'updated_by'  => $ttkSenderId->updated_by,
                'created_at'  => $ttkSenderId->created_at,
                'updated_at'  => $ttkSenderId->updated_at,
            ]);

        }

        echo "Teletalk SenderId Creation Done!";
    }

    public function userBalanceMigrate($request, $response)
    {


        $users = Users::all();

        foreach ($users as $user) {

            $userid = $user->id;

            $client = new Client([
                'verify' => false
            ]);
            $response = $client->post('http://login.smsinbd.com/api/migrate-client-balance', [
                'form_params' => [ 
                    "api_token"     => '6mVDaO2Xi55oRTFNu7MtmOotg2RnVFkPlPrurhT6',
                    "userid"        => $userid
                ]
            ]);
            $arr = json_decode($response->getBody());
            
            

            $mask = $arr->balance->maskbalance;
            $nonmask = $arr->balance->nonmaskbalance;

            if (!$mask ) {
                $mask = 0;
            }
            if (!$nonmask ) {
                $nonmask = 0;
            }

            $user->mask_balance = $mask;
            $user->nonmask_balance = $nonmask;
            $user->save();

            //work end. start from transaction creation
            $transaction = AccountTransaction::create([
                'type'        => 1,
                'user'        => $user->id,
                'txn_type'    => 'opening_balance',
                'reference'   => null,
                'debit'       => 0,
                'credit'      => $mask,
                'balance'     => $mask,
                'note'        => 'Opening Balance Created',
                'active'      => 1,
            ]);

            $transaction = AccountTransaction::create([
                'type'        => 2,
                'user'        => $user->id,
                'txn_type'    => 'opening_balance',
                'reference'   => null,
                'debit'       => 0,
                'credit'      => $nonmask,
                'balance'     => $nonmask,
                'note'        => 'Opening Balance Created',
                'active'      => 1,
            ]);

            echo $user->name.' - '.$mask."--".$nonmask."<br>";
        }

        return "done";
    }

    public function resellerBalanceMigrate($request, $response)
    {


        $users = Reseller::all();

        foreach ($users as $user) {

            $userid = $user->id;

            $client = new Client([
                'verify' => false
            ]);
            $response = $client->post('http://login.smsinbd.com/api/migrate-reseller-balance', [
                'form_params' => [ 
                    "api_token"     => '6mVDaO2Xi55oRTFNu7MtmOotg2RnVFkPlPrurhT6',
                    "userid"        => $userid
                ]
            ]);
            $arr = json_decode($response->getBody());
            

            $mask = $arr->balance->maskbalance;
            $nonmask = $arr->balance->nonmaskbalance;

            if (!$mask ) {
                $mask = 0;
            }
            if (!$nonmask ) {
                $nonmask = 0;
            }

            $user->mask_balance = $mask;
            $user->nonmask_balance = $nonmask;
            $user->save();

            //work end. start from transaction creation
            $transaction = AccountTransaction::create([
                'type'        => 1,
                'user'        => $user->id,
                'is_reseller' => 1,
                'txn_type'    => 'opening_balance',
                'reference'   => null,
                'debit'       => 0,
                'credit'      => $mask,
                'balance'     => $mask,
                'note'        => 'Opening Balance Created',
                'active'      => 1,
            ]);

            $transaction = AccountTransaction::create([
                'type'        => 2,
                'user'        => $user->id,
                'is_reseller' => 1,
                'txn_type'    => 'opening_balance',
                'reference'   => null,
                'debit'       => 0,
                'credit'      => $nonmask,
                'balance'     => $nonmask,
                'note'        => 'Opening Balance Created',
                'active'      => 1,
            ]);

            echo $user->name.' - '.$mask."--".$nonmask."<br>";
        }

        return "done";
    }

    public function testMNP($request, $response)
    {
        $mobile = "01723858781";
        if ($request->getParam('mobile')) {
            $mobile = $request->getParam('mobile');
        }
        $dippingoperator = ["Grameenphone","Banglalink","Airtel","Robi","TeleTalk"];

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
            var_dump($rescollect);
        }

        var_dump($operatorId);
    }


    public function testGP($request, $response)
    {


        $client = new Client([
            'verify' => false
        ]);

        $clientresponse = $client->request('POST','https://gpcmp.grameenphone.com/gpcmpapi/messageplatform/controller.home',[
            'form_params' => [ 
                'username'      => 'DHITAdmin',
                'password'      => 'DHITAdmin$786',
                'apicode'       => '5', //with delivery report request
                'msisdn'        => '01723858781',
                'countrycode'   => '880',
                'cli'           => 'SMSinBD',
                'messagetype'   => '1', //1-text, 2-flash, 3-unicode
                'message'       => 'Greetings! This is a test sms2...',
                'messageid'     => '0',
            ]
        ]);
        echo $clientresponse->getBody(); die();

        $responsedata =  json_decode($clientresponse->getBody());
        // $rescollect = collect($responsedata);

        // var_dump($clientresponse->getBody());
        var_dump($responsedata);
    }
    
    public function testGPDelivery($request, $response)
    {


        $client = new Client([
            'verify' => false
        ]);

        $clientresponse = $client->request('POST','https://gpcmp.grameenphone.com/gpcmpapi/messageplatform/controller.home',[
            'form_params' => [ 
                'username' => 'DHITAdmin',
                'password' => 'DHITAdmin$786',
                'apicode' => '4',//with relivery report request
                // 'msisdn' => '01723858781,0777333678,01717473897,01720065692,01776276385',
                'msisdn' => '01723858781',
                'countrycode' => '0',
                'cli' => 'siliconBD',// just need to be a valid senderid. no restriction to be the exact cli
                'messagetype' => '1', //1-text, 2-flash, 3-unicode
                'message' => '0',
                'messageid' => '20210311-5086-310027507203-01708403007-02',
                // 'messageid' => '20210307-5086-300015631707-01708403007-02',
            ]
        ]);
        echo $clientresponse->getBody(); die();

        $responsedata =  json_decode($clientresponse->getBody());
        // $rescollect = collect($responsedata);

        // var_dump($clientresponse->getBody());
        var_dump($responsedata);
    }
    
    public function testMetro($request, $response)
    {
        
        $client = new Client([
            'verify' => false
        ]);


        try {
            $response = $client->get('http://portal.metrotel.com.bd/smsapi', [
                'query' => [
                    "api_key"      => "R2000184626aae13cb19e4.69465805",
                    "type"         => "text",
                    "contacts"     => "8801723858781",
                    "senderid"     => '8809612442451',
                    "msg"          => 'test sms single by rubel',
                ]
            ]);
        } catch (RequestException $e) {
            $smsSingle->status = 3;
            $smsSingle->save();

            SmsGatewayErrors::create([
                'sms_id'                => $smsSingle->id,
                'type'              => 1, //for campaign sms
                'operator'          => 12, // bl
                'error_code'        => 'Con Failed',
                'error_description' => 'Connection Error',
            ]);

            echo "Gateway Connection Error";
        }

        if (substr($response->getBody(), 0, 2 ) === "10") {
            $smsSingle->status = 3;
            $smsSingle->save();

            echo 'Gateway Error';

        } else {
            //sms send successful
            echo str_replace("SMS SUBMITTED: ID - ","",$response->getBody());
            exit;
            
            //SMS SUBMITTED: ID - 
            $smsSingle->status = 1;
            $smsSingle->save();
            echo  'success';
        }
        
        //
        exit;
        
        echo "metro:";
        $post_url = "http://portal.metrotel.com.bd/smsapi" ;  
      
        $post_values = array( 
        'api_key' => "R2000184626aae13cb19e4.69465805",
        'type' => "text",
        "contacts"     => "8801723858781",
        "senderid"     => '8809612442451',
        "msg"          => 'test sms single by rubel',
        );
    
        $post_string = "";
        foreach( $post_values as $key => $value )
        { $post_string .= "$key=" . urlencode( $value ) . "&"; }
        $post_string = rtrim( $post_string, "& " );
      
        $request = curl_init($post_url);
        //curl_setopt($request, CURLOPT_PORT, '8809');
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
        
        $post_response = curl_exec($request);  
        curl_close ($request);  
        
        var_dump($post_response); exit;

        $array =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $post_response), true );
        print_r($array);
    }

    public function testFusion($request, $response)
    {
        $client = new Client([
            'verify' => false
        ]);

        try {
            $response = $client->get('https://api.fusionbd.net/API/SendSMS', [
                'query' => [
                    "username"      => "datahostit",
                    'apiId'         => "tFJN1EQz",
                    'json'          => 'True',
                    "destination"   => "8801723858781",
                    "source"        => "8809604901100",
                    "text"          => "Test sms by Rubel"
                ]
            ]);
        } catch (RequestException $e) {
            $smsSingle->status = 3;
            $smsSingle->save();

            echo "Gateway Connection Error";
        }

        if (substr($response->getBody(), 0, 2 ) === "10") {
            $smsSingle->status = 3;
            $smsSingle->save();

            echo 'Gateway Error';

        } else {
            //sms send successful
            $responseData = json_decode($response->getBody());
            print_r($responseData);
            echo $responseData->Description;
            echo $responseData->Id;
            exit;
            
            //SMS SUBMITTED: ID - 
            $smsSingle->status = 1;
            $smsSingle->save();
            echo  'success';
        }
        
        echo "done";
        //
        exit;
        
        echo "metro:";
        $post_url = "http://portal.metrotel.com.bd/smsapi" ;  
      
        $post_values = array( 
        'api_key' => "R2000184626aae13cb19e4.69465805",
        'type' => "text",
        "contacts"     => "8801723858781",
        "senderid"     => '8809612442451',
        "msg"          => 'test sms single by rubel',
        );
    
        $post_string = "";
        foreach( $post_values as $key => $value )
        { $post_string .= "$key=" . urlencode( $value ) . "&"; }
        $post_string = rtrim( $post_string, "& " );
      
        $request = curl_init($post_url);
        //curl_setopt($request, CURLOPT_PORT, '8809');
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
        
        $post_response = curl_exec($request);  
        curl_close ($request);  
        
        var_dump($post_response); exit;

        $array =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $post_response), true );
        print_r($array);
    }


    public function usersBalanceCorrection($request, $response)
    {

        if(empty($request->getParam('userAuthVr')) || $request->getParam('userAuthVr')!='vrbrrr'){
            die('invalid request');
        }

        $category = 1;
        // $users = Users::all();
        $adjustments = \App\Models\BalanceAdjustment::where('category', $category)->get();

        foreach ($adjustments as $adjustment){
            $user = Users::find($adjustment->user_id);

            if ($category==2) {
                $senderidType = 'nomask';
                $currentBalance = $user->nonmask_balance;
            } else if ($category==1) {
                $senderidType = 'mask';
                $currentBalance = $user->mask_balance;
            }

            $newBalance = $currentBalance + $adjustment->adjustment;

            
            //update the user's balance
            UserBalanceManager::updateBalance($user->id, $category, $newBalance);

            if($adjustment->adjustment > 0){
                //create the account transaction
                $transaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_cam_refund',
                    'reference'   => '',
                    'debit'       => 0,
                    'credit'      => $adjustment->adjustment,
                    'balance'     => $newBalance,
                    'note'        => $adjustment->adjustment. ' sms refunded after recalculation',
                    'active'      => 1,
                ]);
            } else {
                //create the account transaction
                $transaction = AccountTransaction::create([
                    'type'        => $category,
                    'user'        => $user->id,
                    'txn_type'    => $senderidType.'_cam_refund',
                    'reference'   => '',
                    'debit'       => - $adjustment->adjustment,
                    'credit'      => 0,
                    'balance'     => $newBalance,
                    'note'        => - ($adjustment->adjustment) . ' sms refund cancelled after recalculation',
                    'active'      => 1,
                ]);
            }
            
            echo $user;
        }
        echo "completed transaction update";
        exit;



        foreach ($users as $key=>$user) {

            $userid = $user->id;
            

            $maskBalance = $user->maskbalance;
            $nonmaskBalance = $user->nonmaskbalance;

            if (!$maskBalance ) {
                $maskBalance = 0;
            }
            if (!$nonmaskBalance ) {
                $nonmaskBalance = 0;
            }

            //get user's total used sms mask
            // $smsSingleMaskQty = SmsSingle::where('user_id', '=', $user->id)->where('category', 1)->sum('qty');

            // $smsSingleNonMaskQty = SmsSingle::where('user_id', '=', $user->id)->where('category', 2)->sum('qty');

            // echo $user->name.' - '.$smsSingleMaskQty." - ".$smsSingleNonMaskQty." ----- user_id: ".$user->id."<br>";

            //get all masking campaigns
            $campaigns = SmsCampaigns::where('user_id', '=', $user->id)->where('category', 1)->get();
            $usersTotalFailedMaskCount = 0;
            $usersTotalRefundMaskCount = 0;
            foreach ($campaigns as $campaign) {
                $campaignType = $campaign->campaign_type;
                $failedSmsCount = 0;
                if ($campaignType=='A') {
                    $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='B') {
                    $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='C') {
                    $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='D') {
                    $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='E') {
                    $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                }

                $usersTotalFailedMaskCount += $failedSmsCount * $campaign->sms_qty;

                //finding refund
                $refund = AccountTransaction::where('reference', $campaign->id)->where('txn_type', 'mask_cam_refund')->where('type', 1)->first();

                if ($refund) {
                    $usersTotalRefundMaskCount += $refund->credit;
                }
            }

            //get all non-masking campaigns
            $campaigns = SmsCampaigns::where('user_id', '=', $user->id)->where('category', 2)->get();
            $usersTotalFailedNonMaskCount = 0;
            $usersTotalRefundNonMaskCount = 0;
            foreach ($campaigns as $campaign) {
                $campaignType = $campaign->campaign_type;
                $failedSmsCount = 0;
                if ($campaignType=='A') {
                    $failedSmsCount = SmsCampaignNumbersA::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='B') {
                    $failedSmsCount = SmsCampaignNumbersB::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='C') {
                    $failedSmsCount = SmsCampaignNumbersC::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='D') {
                    $failedSmsCount = SmsCampaignNumbersD::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                } else if ($campaignType=='E') {
                    $failedSmsCount = SmsCampaignNumbersE::where('campaign_id', $campaign->id)->where('status', 3)->orWhere('status', 0)->count();
                }

                $usersTotalFailedNonMaskCount += $failedSmsCount * $campaign->sms_qty;

                //finding refund
                $refund = AccountTransaction::where('reference', $campaign->id)->where('txn_type', 'nonmask_cam_refund')->where('type', 2)->first();

                if ($refund) {
                    $usersTotalRefundNonMaskCount += $refund->credit;
                }


            }

            echo $user->name." --- TotalMaskFailed: ".$usersTotalFailedMaskCount . " --- Total Mask Refund " . $usersTotalRefundMaskCount . " --- TotalNonMaskFailed: ".$usersTotalFailedNonMaskCount. " --- Total Non-Mask Refund : ".$usersTotalRefundNonMaskCount;


            if ($key==20)
                break;
        }

        return "done";
    }

    public function fixUserRefundBalance($request, $response)
    {
        $userId = $request->getParam('userId');

        $user = Users::find($userId);
        if($user){
            // $userLastFixedRefund = AccountTransaction::where('user', $userId)->where();
            $db_conx = $this->DbConnect->connect();
            $sql = "select id from account_transactions where note like '%recalculation' AND user=$userId";
            $query = mysqli_query($db_conx, $sql);
            $result = mysqli_fetch_assoc($query);
            if($result){
                $lastRefundFixedId = $result['id'];

                // $sql = "SELECT *  from account_transactions where account_transactions.id>$lastRefundFixedId and account_transactions.user=$userId";

                // $result = mysqli_fetch_assoc($query);
                $transactions = AccountTransaction::where('user', $userId)->where('id', '>', $lastRefundFixedId)->where('txn_type', 'like', '%_cam_refund')->get();

                echo $transactions;
                // foreach ($transactions as $transaction) {
                //     if($transaction->type==1){

                //     }
                // }
                
            }

            exit;
        }
    }

    public function fixUserBalance($request, $response)
    {
        echo "hi...";
        $clientid = 145;

        $totalPurchasedSms = DB::select("select SUM(credit-debit) AS total_purchased, type  from account_transactions where user =$clientid AND note IN ('Payment Invoice Created', 'Return Invoice Created') GROUP BY type");

        $totalCampaignSms = DB::select("SELECT SUM(sms_qty*total_numbers) total_sent, category as type from sms_campaigns where user_id=$clientid  and active=1 GROUP BY category");

        $totalSingleSms = DB::select("SELECT SUM(qty) total_sent, category as type from sms_individuals where user_id=$clientid  and active=1 GROUP BY category");

        $totalFailedSms = DB::select("SELECT SUM(qty) total_sent, category as type from sms_individuals where user_id=$clientid  and active=1 GROUP BY category");


        $maskPurchase = $nonmaskPurchase = $maskSent = $nonmaskSent = 0;
        foreach ($totalPurchasedSms as $key => $data) {
            if($data->type==1){
                $maskPurchase = $data->total_purchased;
            }
            if($data->type==2){
                $nonmaskPurchase = $data->total_purchased;
            }
        }

        foreach ($totalCampaignSms as $key => $data) {
            if($data->type==1){
                $maskSent = $data->total_sent;
            }
            if($data->type==2){
                $nonmaskSent = $data->total_sent;
            }
        }
        foreach ($totalSingleSms as $key => $data) {
            if($data->type==1){
                $maskSent += $data->total_sent;
            }
            if($data->type==2){
                $nonmaskSent += $data->total_sent;
            }
        }

        $totalData['maskPurchase'] = $maskPurchase;
        $totalData['nonmaskPurchase'] = $nonmaskPurchase;
        $totalData['maskSent'] = $maskSent;
        $totalData['nonmaskSent'] = $nonmaskSent;

        print_r($totalData); exit;
    }
}

