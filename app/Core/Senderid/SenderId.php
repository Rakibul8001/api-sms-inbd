<?php

namespace App\Core\Senderid;

interface SenderId
{
    /**
     * Add Sender Id
     *
     * @param array $data
     * @return void
     */
    public function addSenderId(array $data);

    /**
     * Update Sender Id
     *
     * @param array $data
     * @return void
     */
    public function updateSenderId(array $data);

    /**
     * Delete Sender Id
     *
     * @param int $id
     * @return void
     */
    public function deleteSenderId($id);


    /**
     * Show SMS Sender Ids
     *
     * @return void
     */
    public function showSmsSenderId($sendertype=null);

    /**
     * Show Reseller SMS Sender Ids
     *
     * @return void
     */
    public function showResellerSmsSenderId($resellerid = null);


    /**
     * Check the sender id, exist in database
     *
     * @param int $id
     * @return void
     */
    public function isValidSenderId($id);

    /**
     * Show senderid by its record id
     *
     * @param int $senderid
     * @return void
     */

    public function getSenderIdById($senderid);
    /**
     * Show senderid by its name
     *
     * @param int $senderid
     * @return void
     */
    public function getSenderIdByName($senderid);

    /**
     * Show teletalk senderid by its name
     *
     * @param int $senderid
     * @return void
     */
    public function getTeletalkSenderIdByName($senderid);
}