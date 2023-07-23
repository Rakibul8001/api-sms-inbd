<?php

namespace App\Core\Accounts;

use App\Account;
use App\Core\Accounts\Accounts;
use App\Http\Resources\AccountResource;

class AccountDetails implements Accounts
{
    /**
     * Add new voucher
     *
     * @param array $data
     * @return void
     */
    public function addVoucher(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'],406);
        }

        return new AccountResource(Account::create([
                'account_head_id' => $data['account_head_id'],
                'account_parent_id' => $data['account_parent_id'],
                'amount_dr' => $data['amount_dr'],
                'amount_cr' => $data['amount_cr'],
                'user_id' => $data['user_id'],
                'voucher_owner' => $data['voucher_owner'],
                'voucher_owner_id' => $data['voucher_owner_id'],
                'voucher_id' => $data['voucher_id'],
                'transection_id' => $data['transection_id'],
                'voucher_date' => $data['voucher_date'],
                'voucher_create_origin' => $data['voucher_create_origin']
        ]));
    }

    /**
     * Show voucher by voucherid
     *
     * @param string $voucher_id
     * @return void
     */
    public function showVoucherByVoucherId($voucher_id)
    {

    }

    /**
     * Edit voucher by voucherid
     *
     * @param string $voucher_id
     * @return void
     */
    public function editVoucher($voucher_id)
    {

    }

    /**
     * Update voucher
     *
     * @param array $data
     * @return void
     */
    public function updateVoucher(array $data)
    {

    }

    /**
     * Delete voucher
     *
     * @param string $voucher_id
     * @return void
     */
    public function deleteVoucher($voucher_id)
    {

    }
}