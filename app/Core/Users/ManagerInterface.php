<?php

namespace App\Core\Users;

interface ManagerInterface
{
    /**
     * Add support manager
     *
     * @param array $data
     * @return void
     */
    public function addManager(array $data);

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
     * Update support manager
     *
     * @param array $data
     * @return void
     */
    public function managerUpdate(array $data);

    /**
     * Get total support managers
     *
     * @return void
     */
    public function totalSupportManagers();
}