<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsDeliveryReports extends Model
{
    protected $table = 'sms_delivery_reports';
    
    protected $fillable = [
        'sms_id',
        'type',
        'campaign_type',
        'operator',
        'reference',
        'status',
        'description',
        'created_at',
        'updated_at',
        'active',
    ];

}