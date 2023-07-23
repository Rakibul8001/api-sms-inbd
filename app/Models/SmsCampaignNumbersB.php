<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignNumbersB extends Model
{
    protected $table = 'sms_campaign_numbersB';
    public $timestamps = false;
    protected $fillable = [
        'campaign_id',
        'number',
        'operator',
        'status',
        'active'
    ];
}
