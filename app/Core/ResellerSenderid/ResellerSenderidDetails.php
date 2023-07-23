<?php

namespace App\Core\ResellerSenderid;

use App\Reseller;
use App\ResellerSender;
use App\Http\Resources\ResellerResource;
use App\Core\ResellerSenderid\ResellerSenderid;
use App\Http\Resources\ResellerSenderidResource;
use App\SmsSender;

class ResellerSenderidDetails implements ResellerSenderid
{
    /**
     * Assign senderid to the client
     *
     * @param array $data
     * @return void
     */
    public function assignSenderIdToReseller(array $data)
    {
        if (! is_array($data))
        {
            return Response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        
        return new ResellerSenderidResource(ResellerSender::create([
            'reseller_id' => $data['reseller_id'],
            'sms_sender_id' => $data['sms_sender_id'],
            'status' => $data['status'],
            'default' => $data['default'],
            'created_by' => $data['created_by'],
            'updated_by' => $data['updated_by'],
            'user_type' => $data['user_type']
        ]));
    }

    /**
     * Show assigned senderid to client
     *
     * @param int $clientid
     * @return void
     */
    public function showResellerSenderId($resellerid)
    {
        return ResellerResource::collection(
            Reseller::where('id',$resellerid)
                  ->with(['senderids' => function($query) use ($resellerid){
                            $query->where('reseller_id',$resellerid);
                        }])
            ->get());
    }


    /**
     * Show assigned senderid to client
     *
     * @param int $clientid
     * @return void
     */
    public function showResellerAssignedSenderId($resellerid)
    {
        return SmsSender::whereIn('id', function($query) use ($resellerid){
            $query->select('sms_sender_id')
            ->from('reseller_senders')
            ->where('reseller_id', $resellerid);
        })->get();
    }
    
    /**
     * Get clients , where senderid not assigned yet
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerWithoutSenderId($senderid)
    {
        return ResellerResource::collection(Reseller::whereNotIn('id', function($query) use($senderid){
            $query->select('reseller_id')
            ->from('reseller_senders')
            ->where('sms_sender_id', $senderid)
            ->get();
        })->get());
    }

    /**
     * Get client list of assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerWithSenderId($senderid)
    {
        return ResellerResource::collection(Reseller::with(['senderid' => function($query) use ($senderid){
            $query->where('sms_sender_id',$senderid);
        }])->get());
    }

    /**
     * Get all clients of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getAllResellersWithSenderId($senderid)
    {
        return ResellerResource::collection(Reseller::with(['senderids' => function($query) use ($senderid){
            $query->where('sms_sender_id',$senderid);
        }])->get());
    }

    /**
     * Delete assigned senderid of a client 
     *
     * @param int $senderid
     * @return void
     */
    public function deleteResellerAssignedSenderId($assigned_reseller_senderid, $senderid)
    {
        $check = ResellerSender::where('sms_sender_id', $senderid)
        ->where('reseller_id',$assigned_reseller_senderid)
        ->first();

        if ($check->userAssignedSenderId->isEmpty())
        {            
            new ResellerSenderidResource(ResellerSender::where('sms_sender_id', $senderid)
                                                ->where('reseller_id', $assigned_reseller_senderid)
                                                ->delete()
                                    );

            return response()->json(['msg' => 'Senderid deleted successfully'],200);
        }

        return response()->json(['errmsg' => 'Child record found, You can not delete parent senderid'], 406);
    }
}