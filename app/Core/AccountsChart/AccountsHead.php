<?php

namespace App\Core\AccountsChart;

interface AccountsHead
{
    /**
     * Add accounts root head
     *
     * @param array $data
     * @return void
     */
    public function addAccountsHead(array $data);

    /**
     * Edit an accounts head by id 
     *
     * @param int $id
     * @return void
     */
    public function editAccountsHead(array $data);

    /**
     * Get an accounts head by id
     *
     * @param int $id
     * @return void
     */
    public function getAccountsHeadById($id);

    /**
     * Get all accounts head
     *
     * @return void
     */
    public function getAllRootAccountsHead();

    /**
     * Get all group accounts head
     *
     * @return void
     */
    public function getAllGroupAccountsHead();


    /**
     * Get all account's head under a group
     *
     * @param int $group_id
     * @return void
     */
    public function getAllGroupAccountsHeadById($group_id);

    /**
     * Get all transection accounts head
     *
     * @return void
     */
    public function getAllTransectionAccountsHead();

    /**
     * Delete accounts head
     *
     * @param int $id
     * @return void
     */
    public function deleteAccountsHead($id);
}