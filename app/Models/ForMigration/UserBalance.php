<?php

namespace App\Models\ForMigration;

use Illuminate\Database\Eloquent\Model;

class UserBalance extends Model
{
    protected $table = 'user_balance';

    protected $fillable = [
        'userid',
        'mask',
        'nonmask',
        'voice',
        'balance_date'
    ];
}
