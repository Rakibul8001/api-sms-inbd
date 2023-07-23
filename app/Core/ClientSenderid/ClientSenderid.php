<?php

namespace App\Core\ClientSenderid;

interface ClientSenderid
{
    /**
     * Assign senderid to client
     *
     * @param array $data
     * @return void
     */
    public function assignSenderIdToClient(array $data);

    /**
     * Show assigned senderid of client
     *
     * @param int $clientid
     * @return void
     */
    public function showClientSenderId($clientid);

    /**
     * Show assigned senderid to reseller client
     *
     * @param int $clientid
     * @return void
     */
    public function showResellerClientSenderId($clientid);


    /**
     * Set client default senderid
     *
     * @param int $clientid
     * @return void
     */
    public function clientDefaultSenderId($clientid,$senderid);

    /**
     * Get reseller clients , where senderid not assigned yet
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerClientsWithoutSenderId($senderid);

    /**
     * Get client list where senderid not assigned yet
     *
     * @param int $senderid
     * @return void
     */
    public function getClientsWithoutSenderId($senderid);


    /**
     * Get reseller client list of assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getResellerClientWithSenderId($senderid);

    /**
     * Get client of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getClientWithSenderId($senderid);

    /**
     * Get all clients of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getAllClientsWithSenderId($senderid);

    /**
     * Get all reseller clients of a assigned senderid
     *
     * @param int $senderid
     * @return void
     */
    public function getAllResellerClientsWithSenderId($senderid);

    /**
     * Delete assinged senderid of a client
     *
     * @param int $senderid
     * @return void
     */
    public function deleteClientAssignedSenderId($assigned_user_senderid, $senderid);

    /**
     * Delete assigned senderid of a reseller client 
     *
     * @param int $senderid
     * @return void
     */
    public function deleteResellerClientAssignedSenderId($assigned_user_senderid, $senderid);

    /**
     * Show assigned senderid to client
     *
     * @param int $clientid
     * @return void
     */
    public function showClientAssignedSenderId($clientid);
}