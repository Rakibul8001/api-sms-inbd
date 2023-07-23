<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'contact_group_id',
        'contact_name',
        'contact_number',
        'contact_file_address',
        'email',
        'gender',
        'dob',
        'status',
    ];

    public function contactGroup()
    {
        return $this->belongsTo(ContactGroup::class,'contact_group_id','id');
    }
}