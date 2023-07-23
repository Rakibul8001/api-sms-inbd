<?php

namespace App\Core\SmsSend;

interface SmsSend
{

    /**
     * Get senderid type mask|non mask|voice
     *
     * @param array $data
     * @return void
     */
    public function getSenderIdType(array $data);

    /**
     * Get total contacts in a contact group
     *
     * @param array $data
     * @return void
     */
    public function getTotalNumberOfContacts(array $data);

    /**
     * Valid mobile number
     *
     * @param int $userid
     * @param string $contacts
     * @return void
     */
    public function validMobileFromFile($userid, $contactlist);

    /**
     * Get total number valid contact in a uploaded file
     *
     * @return void
     */
    public function totalValidContactInAFile();

    /**
     * Valid mobile number
     *
     * @param int $userid
     * @param int $groupid
     * @return void
     */
    public function validMobile($userid, $groupid);


    /**
     * Valid mobile number
     *
     * @param int $userid
     * @param int $groupid
     * @return void
     */
    public function validMobileFromRequest(array $clientcontacts);


    /**
     * Get total number valid contact in a group
     *
     * @return void
     */
    public function totalValidContactInAGroup();


    /**
     * Get total number valid contact in a request
     *
     * @return void
     */
    public function totalValidContactInARequest(array $clientcontacts);


    /**
     * Manage sms message length in runtime
     *
     * @return void
     */
    public function manageSmsMessageCount($message);

    /**
     * Determine sms message is unicode content | normal text content
     *
     * @param string $message
     * @return void
     */
    public function smsMessageType($message);


    /**
     * Get sender id information
     *
     * @param int $senderid
     * @return void
     */
    public function getSenderIdInformationBySenderId($senderid);


    /**
     * Get sender id information
     *
     * @param int $senderid
     * @return void
     */
    public function getSenderIdInformationBySenderName($senderid);


    public function smsSendToTelitalk(array $data);

    public function smsSendToGp(array $data);

    public function smsSendToBlink(array $data);

    public function smsSendToRobi(array $data);
    
    public function smsSendToEasyWeb(array $data);

    public function smsSendToRanksTel(array $data);

}