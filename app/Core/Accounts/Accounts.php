<?php

namespace App\Core\Accounts;

interface Accounts
{
    /**
     * Add new voucher
     *
     * @param array $data
     * @return void
     */
    public function addVoucher(array $data);

    /**
     * Show voucher by voucherid
     *
     * @param string $voucher_id
     * @return void
     */
    public function showVoucherByVoucherId($voucher_id);

    /**
     * Edit voucher by voucherid
     *
     * @param string $voucher_id
     * @return void
     */
    public function editVoucher($voucher_id);

    /**
     * Update voucher
     *
     * @param array $data
     * @return void
     */
    public function updateVoucher(array $data);

    /**
     * Delete voucher
     *
     * @param string $voucher_id
     * @return void
     */
    public function deleteVoucher($voucher_id);
}