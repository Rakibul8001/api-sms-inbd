<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignNumbers extends Model
{
    protected $table = 'sms_campaign_numbers';
    public $timestamps = false;
    protected $fillable = [
        'campaign_id',
        'number',
        'operator',
        'error_id',
        'status',
        'active'
    ];
}
