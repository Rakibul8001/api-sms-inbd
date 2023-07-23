<?php 

namespace App\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Str;
use Illuminate\Database\Capsule\Manager as DB;
// use Illuminate\Support\Facades\DB;


//include models
use App\Models\SmsLowcostCampaigns;
use App\Models\SmsLowcost;


class ModemController extends Controller
{

    public function getModemSms($request, $response)
    {
        
        //get the modem
        $api_token = $request->getParam('api_token');
        $modem = $this->auth->modem($api_token);
        if($modem->active==0){
            return $response->withJson([
                'status'        => "error",
                'message'       => 'Inactive Modem',
                'StatusCode'    => '102',
            ], 200);
        }

        DB::beginTransaction();

        $smsData = SmsLowcost::where('status', 0)->lockForUpdate()->first();

        if($smsData){
            $smsData->status = 1;
            $smsData->route_id = $modem->id;
            $smsData->save();

            DB::commit();

            //operator code
            if ($smsData->operator==1) {
                $operator = "GP";
            } else if ($smsData->operator==2) {
                $operator = "BL";
            } else if ($smsData->operator==3) {
                $operator = "Airtel";
            } else if ($smsData->operator==4) {
                $operator = "Robi";
            } else if ($smsData->operator==5) {
                $operator = "Teletalk";
            }

            //return the sms data
            return $response->withJson([
                'status'        => "success",
                'sms_id'        => $smsData->user_id."-".$smsData->id,
                'number'        => $smsData->number,
                'message'       => $smsData->content,
                'operatorName'  => $operator,
                'operatorCode'  => $smsData->operator,
                'is_unicode'    => $smsData->is_unicode,
                'StatusCode'    => '100',
            ], 200);


        } else {
            DB::rollBack();

            //return no sms to send

            return $response->withJson([
                'status'        => "error",
                'message'       => 'No SMS to Send',
                'StatusCode'    => '101',
            ], 200);
        }

        
        //return system error
        return $response->withJson([
                'status'        => "error",
                'message'       => 'Something went wrong',
                'StatusCode'    => '103',
            ], 200);

    }

    public function postSmsStatus($request, $response)
    {
        
        //get the modem
        $api_token = $request->getParam('api_token');
        $modem = $this->auth->modem($api_token);
        if($modem->active==0){
            return $response->withJson([
                'status'        => "error",
                'message'       => 'Inactive Modem',
                'StatusCode'    => '102',
            ], 200);
        }

        
        if (!$request->getParam('sms_id') || !$request->getParam('status')) {
            return $response->withJson([
                'status'        => "error",
                'message'       => 'Parameter Missing',
                'StatusCode'    => '105',
            ], 200);
        }

        $smsId = explode('-', $request->getParam('sms_id'));

        $smsData = SmsLowcost::find($smsId[1]);

        if($smsData){
            
            if($smsData->user_id!=$smsId[0]){
                return $response->withJson([
                    'status'        => "error",
                    'message'       => 'Invalid SMS ID',
                    'StatusCode'    => '106',
                ], 200);
            }

            $requestStatus = $request->getParam('status');
            if ($requestStatus==2) {
                $status = 3;
            } else if ($requestStatus==1){
                $status = 2;
            } else {
                //invalid status code
                return $response->withJson([
                    'status'        => "error",
                    'message'       => 'Invalid Status Code',
                    'StatusCode'    => '107',
                ], 200);
            }

            if ($smsData->status==2) {
                //already posted sms status
                return $response->withJson([
                    'status'        => "error",
                    'message'       => 'Already posted sms status',
                    'StatusCode'    => '108',
                ], 200);
            }

            $smsData->status = $status;
            $smsData->save();


            //return the sms data
            return $response->withJson([
                'status'        => "success",
                'message'       => "Successfully updated status",
                'StatusCode'    => '100',
            ], 200);


        } else {

            //invalid sms id
            return $response->withJson([
                    'status'        => "error",
                    'message'       => 'Invalid SMS ID',
                    'StatusCode'    => '106',
                ], 200);
        }

        
        //return system error
        return $response->withJson([
                'status'        => "error",
                'message'       => 'Something went wrong',
                'StatusCode'    => '103',
            ], 200);
    }

    public function updateModemStatus($request, $response)
    {
        //get the modem
        $api_token = $request->getParam('api_token');
        $modem = $this->auth->modem($api_token);
        if($modem->active==0){
            return $response->withJson([
                'status'        => "error",
                'message'       => 'Inactive Modem',
                'StatusCode'    => '102',
            ], 200);
        }

        if (!$request->getParam('status')) {
            return $response->withJson([
                'status'        => "error",
                'message'       => 'Parameter Missing',
                'StatusCode'    => '105',
            ], 200);
        }

        $statusCode = $request->getParam('status');
        $status = "";
        if ($statusCode=='active') {
            $status = 1;
        } else if($statusCode=='down') {
            $status = 0;
        } else {
            return $response->withJson([
                'status'        => "error",
                'message'       => 'Invalid Status Code',
                'StatusCode'    => '107',
            ], 200);
        }

        

        if ($status==0 || $status==1) {

            $modem->status = $status;
            $modem->save();

            return $response->withJson([
                'status'        => "success",
                'message'       => "Successfully updated status",
                'StatusCode'    => '100',
            ], 200);
        }
        
        //return system error
        return $response->withJson([
                'status'        => "error",
                'message'       => 'Something went wrong',
                'StatusCode'    => '103',
            ], 200);


    }
}