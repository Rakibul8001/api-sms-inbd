<?php

namespace App\Models\ForMigration;

use Illuminate\Database\Eloquent\Model;

class ArchiveSentSms extends Model
{
    protected $table = 'archive_sent_smses';
    
    protected $fillable = [
        'remarks',
        'user_id',
        'user_sender_id',
        'to_number',
        'sms_type',
        'sms_catagory',
        'sms_content',
        'number_of_sms',
        'total_contacts',
        'send_type',
        'contact_group_id',
        'status',
        'submitted_at'
    ];
}
