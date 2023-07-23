<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignNumbersC extends Model
{
    protected $table = 'sms_campaign_numbersC';
    public $timestamps = false;
    protected $fillable = [
        'campaign_id',
        'number',
        'operator',
        'status',
        'active'
    ];
}
