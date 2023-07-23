<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaigns extends Model
{
    protected $table = 'sms_campaigns';
    
    protected $fillable = [
        'campaign_type',
        'campaign_no',
        'campaign_name',
        'campaign_description',
        'user_id',
        'sender_id',
        'is_unicode',
        'category',
        'content',
        'sms_qty',
        'total_numbers',
        'failed_sms',
        'fetched_delivery',
        'sent_through',
        'is_scheduled',
        'scheduled_time',
        'status',
        'created_at',
        'updated_at',
        'active'
    ];

    public function getSenderId()
    {
        return $this->belongsTo(SenderidMaster::class,'id','sender_id');
    }


}
