<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    protected $table = 'account_transactions';
    protected $fillable = [
        'type',
        'user',
        'is_reseller',
        'txn_type',
        'reference',
        'debit',
        'credit',
        'balance',
        'note',
        'created_at',
        'active'
    ];

}
