<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactGroup extends Model
{
    protected $fillable = [
        'user_id',
        'group_name',
        'status'  
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class,'contact_group_id','id');
    }

    public function totalContacts()
    {
        return $this->hasMany(Contact::class,'contact_group_id','id')->count();
    }
}
