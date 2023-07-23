<?php

namespace App\Core\ClientSenderid;

use App\User;
use App\UserSender;
use App\Http\Resources\UserResource;
use App\Core\ClientSenderid\ClientSenderid;
use App\Http\Resources\ClientSenderidResource;
use App\Http\Resources\SmsSenderIdResource;
use App\Http\Resources\UserSentSmsResource;
use App\SmsSender;
use App\UserSentSms;
use Illuminate\Support\Facades\Auth;

class ClientSenderidDetails implements ClientSenderid
{
    /**
     * Assign senderid to the client
     *
     * @param array $data
     * @return void
     */
    public function assignSenderIdToClient(array $data)
    {
        if (! is_array($data))
        {
            return Response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        
        return new ClientSenderidResource(UserSender::create([
            'user_id' => $data['user_id'],
            'sms_sender_id' => $data['sms_sender_id'],
            'status' => $data['status'],
            'default' => $data['default'],
            'created_by' => $data['created_by'],
            'updated_by' => $data['updated_by'],
            'user_type' => $data['user_type']
        ]));
    }

    /**
     * Show assigned senderid to reseller client
     *
     * @param int $clientid
     * @return void
     */
    public function showResellerClientSenderId($clientid)
    {
        return UserResource::collection(
            User::where('id',$clientid->id)
                  ->with(['senderids' => function($query) use ($clientid){
                            $query->where('user_id',$clientid->id)
                                  ->where('created_by', Auth::guard('reseller')->user()->id)
                                  ->where('user_type','reseller');
                        }])
            ->get());
    }

    /**
     * Set client default senderid
     *
     * @param int $clientid
     * @return void
     */
    public function clientDefaultSenderId($clientid,$senderid)
    {
        $checkuser = UserSender::where('sms_sender_id', $senderid)
        ->where('user_id',$clientid)
        ->first();

        if ($checkuser->default == 1)
        {
            $checkuser->default = 0;
            $checkuser->save();

            return $checkuser->default;
        } else {
            $checkuser->default = 1;
            $checkuser->save();

            return $checkuser->default;
        }

        

        return 0;


    }

    /**
     * Show assigned senderid to client
     *
     * @param int $clientid
     * @return void
     */
    public function showClientSenderId($clientid)
    {
        return UserResource::collection(
            User::where('id',$clientid->id)
                  ->with(['senderids' => function($query) use ($clientid){
                            $query->where('user_id',$clientid->id);
                        }])
            ->get());
    }

    /**
     * Get reseller clients , where senderid not assigned yet
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerClientsWithoutSenderId($senderid)
    {
        return UserResource::collection(User::whereNotIn('id', function($query) use($senderid){
            $query->select('user_id')
            ->from('user_senders')
            ->where('sms_sender_id', $senderid)
            ->get();
        })->where('reseller_id',Auth::guard('reseller')->user()->id)->where('created_by','reseller')->get());
    }

    
    /**
     * Get clients , where senderid not assigned yet
     *
     * @param int $senderid
     * @return void
     */
    public function getClientsWithoutSenderId($senderid)
    {
        return UserResource::collection(User::whereNotIn('id', function($query) use($senderid){
            $query->select('user_id')
            ->from('user_senders')
            ->where('sms_sender_id', $senderid)
            ->get();
        })->get());
    }

    /**
     * Get reseller client list of assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerClientWithSenderId($senderid)
    {
        return UserResource::collection(User::with(['senderid' => function($query) use ($senderid){
            $query->where('sms_sender_id',$senderid);
        }])->where('reseller_id',Auth::guard('reseller')->user()->id)->where('created_by','reseller')->get());
    }

    /**
     * Get client list of assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getClientWithSenderId($senderid)
    {
        return UserResource::collection(User::with(['senderid' => function($query) use ($senderid){
            $query->where('sms_sender_id',$senderid);
        }])->get());
    }

    /**
     * Get all reseller clients of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getAllResellerClientsWithSenderId($senderid)
    {
        return UserResource::collection(User::with(['senderids' => function($query) use ($senderid){
            $query->where('sms_sender_id',$senderid);
        }])->where('reseller_id',Auth::guard('reseller')->user()->id)->where('created_by','reseller')->get());
    }

    /**
     * Get all clients of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getAllClientsWithSenderId($senderid)
    {
        return UserResource::collection(User::with(['senderids' => function($query) use ($senderid){
            $query->where('sms_sender_id',$senderid);
        }])->get());
    }

    /**
     * Delete assigned senderid of a client 
     *
     * @param int $senderid
     * @return void
     */
    public function deleteClientAssignedSenderId($assigned_user_senderid, $senderid)
    {
        $check = UserSender::where('sms_sender_id', $senderid)
        ->where('user_id',$assigned_user_senderid)
        ->first();

        if ($check->userSentSms->isEmpty())
        {            
            new ClientSenderidResource(UserSender::where('sms_sender_id', $senderid)
                                                ->where('user_id', $assigned_user_senderid)
                                                ->delete()
                                    );

            return response()->json(['msg' => 'Senderid deleted successfully'],200);
        }

        return response()->json(['errmsg' => 'Child record found, You can not delete parent senderid'], 406);
    }

    /**
     * Delete assigned senderid of a reseller client 
     *
     * @param int $senderid
     * @return void
     */
    public function deleteResellerClientAssignedSenderId($assigned_user_senderid, $senderid)
    {
        $check = UserSender::where('sms_sender_id', $senderid)
        ->where('user_id',$assigned_user_senderid)
        ->where('created_by', Auth::guard('reseller')->user()->id)
        ->where('user_type','reseller')
        ->first();

        if ($check->userSentSms->isEmpty())
        {            
            new ClientSenderidResource(UserSender::where('sms_sender_id', $senderid)
                                                ->where('user_id', $assigned_user_senderid)
                                                ->where('created_by', Auth::guard('reseller')->user()->id)
                                                ->where('user_type','reseller')
                                                ->delete()
                                    );

            return response()->json(['msg' => 'Senderid deleted successfully'],200);
        }

        return response()->json(['errmsg' => 'Child record found, You can not delete parent senderid'], 406);
    }

    /**
     * Show assigned senderid to client
     *
     * @param int $clientid
     * @return void
     */
    public function showClientAssignedSenderId($clientid)
    {
        return SmsSender::whereIn('id', function($query) use ($clientid){
            $query->select('sms_sender_id')
            ->from('user_senders')
            ->where('user_id', $clientid);
        })->get();
    }
}