<?php

namespace App\Core\ContactsAndGroups;

use Illuminate\Http\Request;

interface ContactsAndGroups
{
    /**
     * Create a group
     *
     * @param array $data
     * @return void
     */
    public function createGroup(array $data);

    /**
     * Show all groups
     *
     * @return void
     */
    public function showGroups();

    /**
     * Get a group by id
     *
     * @param int $groupid
     * @return void
     */
    public function getGroupById($groupid);

    /**
     * Get all groups uder a client
     *
     * @param int $clientid
     * @return void
     */
    public function getGroupsByClient($clientid);


    /**
     * Get contact group by clientid and groupid
     *
     * @param int $clientid
     * @param int $groupid
     * @return void
     */
    public function getGroupByClientAndId($clientid, $groupid);


    /**
     * Upload client contacts in a group
     *
     * @param Request $request
     * @return void
     */
    public function addContactFile(Request $request);

    /**
     * Get file extension
     *
     * @return void
     */
    public function getFileExtension();

    /**
     * Get uploaded filename
     *
     * @return void
     */
    public function getFileName();

    /**
     * Get BD Mobile number from csv file
     *
     * @return void
     */
    public function getBdMobileNumberFromCSV();


    /**
     * Get BD Mobile number from Xls or Xlsx
     *
     * @return void
     */
    public function getBDMobileNumberFromXlsOrXlsx();


    /**
     * Get BD Mobile number from text file
     *
     * @return void
     */
    public function getBDMobileNumberFromTextFile();

    /**
     * Update a group
     *
     * @param array $data
     * @return void
     */
    public function updateGroup(array $data);

    /**
     * Delete a group
     *
     * @param int $groupid
     * @return void
     */
    public function deleteGroup($groupid);

    /**
     * Add Contact group
     *
     * @param array $data
     * @return void
     */
    public function addContactGroup(array $data);

    /**
     * Show all contact in groups
     *
     * @return void
     */
    public function showContactGroups();

    /**
     * Get all contacts in a group
     *
     * @param int $contact_group_id
     * @return void
     */
    public function getContactGroupById($contact_group_id);

    /**
     * Get contact by groupid and contactid
     *
     * @param int $contact_group_id
     * @param int $contactid
     * @return void
     */
    public function getContactByGroupAndContactId($contact_group_id, $contactid);


    /**
     * Get contact by groupid and contactid
     *
     * @param int $contact_group_id
     * @param int $contactid
     * @return void
     */
    public function getContactByGroupAndContactNumber($contact_group_id, $contactnumber);

    /**
     * Update contact group
     *
     * @param array $data
     * @return void
     */
    public function updateContactGroup(array $data);

    /**
     * Update contact by contact number
     *
     * @param array $data
     * @return void
     */
    public function updateContactGroupByContactNumber(array $data);

    /**
     * Delete all contacts in a group
     *
     * @param int $contact_group_id
     * @return void
     */
    public function deleteContactGroup($contactid);
}