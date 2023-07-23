<?php

namespace App\Core\ResellerSenderid;

interface ResellerSenderid
{
    /**
     * Assign senderid to client
     *
     * @param array $data
     * @return void
     */
    public function assignSenderIdToReseller(array $data);

    /**
     * Show assigned senderid of client
     *
     * @param int $clientid
     * @return void
     */
    public function showResellerSenderId($clientid);


    /**
     * Get client list where senderid not assigned yet
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerWithoutSenderId($senderid);


    /**
     * Get client of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerWithSenderId($senderid);

    /**
     * Get all clients of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getAllResellersWithSenderId($senderid);

    /**
     * Delete assinged senderid of a client
     *
     * @param int $senderid
     * @return void
     */
    public function deleteResellerAssignedSenderId($assigned_user_senderid, $senderid);
}