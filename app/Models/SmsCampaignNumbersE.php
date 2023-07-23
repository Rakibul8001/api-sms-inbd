<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignNumbersE extends Model
{
    protected $table = 'sms_campaign_numbersE';
    public $timestamps = false;
    protected $fillable = [
        'campaign_id',
        'number',
        'operator',
        'status',
        'active'
    ];
}
