<?php 

namespace App\Core\Users;

interface ResellerInterface
{
    /**
     * Add clients
     *
     * @param array $data
     * @return void
     */
    public function addReseller(array $data);

    /**
     * Show client data
     *
     * @return void
     */
    public function showClients();

    /**
     * Update reseller user
     *
     * @param array $data
     * @return void
     */
    public function resellerUserUpdate($data);

    /**
     * Get total resellers
     *
     * @return void
     */
    public function totalResellers();

    /**
     * Active resellers list
     *
     * @return void
     */
    public function activeResellers();
    
}