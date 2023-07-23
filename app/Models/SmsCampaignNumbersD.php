<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignNumbersD extends Model
{
    protected $table = 'sms_campaign_numbersD';
    public $timestamps = false;
    protected $fillable = [
        'campaign_id',
        'number',
        'operator',
        'status',
        'active'
    ];
}
