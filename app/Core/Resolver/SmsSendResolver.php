<?php

namespace App\Core\Resolver;

use App\Core\SmsSend\SmsSendDetails;

class SmsSendResolver
{
    protected $smssend;

    public function __construct(SmsSendDetails $smssend)
    {
        $this->smssend = $smssend;
    }

    public function resolve() 
    {
        return $this->smssend;
    }
}