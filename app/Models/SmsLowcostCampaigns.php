<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLowcostCampaigns extends Model
{
    protected $table = 'sms_lowcost_campaigns';
    
    protected $fillable = [
        'campaign_no',
        'campaign_name',
        'campaign_description',
        'user_id',
        'is_unicode',
        'content',
        'sms_qty',
        'total_numbers',
        'status',
        'created_at',
        'updated_at',
        'active'
    ];


}
