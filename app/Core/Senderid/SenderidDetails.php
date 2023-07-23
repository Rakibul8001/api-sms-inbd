<?php

namespace App\Core\Senderid;

use DB;
use App\Core\Senderid\SenderId;
use App\Http\Resources\SmsSenderIdResource;
use App\SmsSender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SenderidDetails implements SenderId
{


    public function getSenderIdById($senderid)
    {
        if (! isset($senderid) || empty($senderid))
        {
            return response()->json(['errmsg' => 'Senderid missing'], 406);
        }

        return new SmsSenderIdResource(SmsSender::where('id',$senderid)->first());
    }

    public function getSenderIdByName($senderid)
    {
        if (! isset($senderid) || empty($senderid))
        {
            return response()->json(['errmsg' => 'Senderid missing'], 406);
        }

        if (SmsSender::where('sender_name',$senderid)->where('gateway_info','!=',NULL)->exists())
        {
            return new SmsSenderIdResource(SmsSender::where('sender_name',$senderid)->where('gateway_info','!=',NULL)->first());
        } 
        
        if (SmsSender::where('sender_name',$senderid)->where('gateway_info','=',NULL)->exists())
        {
            return new SmsSenderIdResource(SmsSender::where('sender_name',$senderid)->where('gateway_info','=',NULL)->first());
        }

        return response()->json(['errmsg' => 'Sender Id Not Found'], 406);
    }

    public function getTeletalkSenderIdByName($senderid)
    {
        if (! isset($senderid) || empty($senderid))
        {
            return response()->json(['errmsg' => 'Senderid missing'], 406);
        }

        if (SmsSender::where('sender_name',$senderid)->where('gateway_info','=',NULL)->exists())
        {
            return new SmsSenderIdResource(SmsSender::where('sender_name',$senderid)->where('gateway_info','=',NULL)->first());
        }
    }
}