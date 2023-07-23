<?php

namespace App\Core\Users;

use App\RootUser;

interface RootUserInterface
{
    /**
     * Add root application user
     *
     * @param array $data
     * @return void
     */
    public function addRoot(array $data);

    /**
     * Root user data
     *
     * @return void
     */
    public function showRootUsers();

    /**
     * Show support manager data
     *
     * @return void
     */
    public function showManagers();

    /**
     * Show reseller data
     *
     * @return void
     */
    public function showResellers();

    /**
     * Show client data
     *
     * @return void
     */
    public function showClients();

    /**
     * Root user edit FORM
     *
     * @param RootUser $user
     * @return void
     */
    public function rootUserEdit(RootUser $user);

    /**
     * Root user updated
     *
     * @param array $data
     * @return void
     */
    public function rootUserUpdate(array $data);

}