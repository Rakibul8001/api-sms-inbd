<?php

namespace App\Core\Users;

interface ClientInterface
{
    /**
     * Add client user
     *
     * @param array $data
     * @return void
     */
    public function addClient(array $data);

    /**
     * Add client user
     *
     * @param array $data
     * @return void
     */
    public function activeResellerClients($resellerid);


    /**
     * Update client
     *
     * @param array $data
     * @return void
     */
    public function clientUpdate(array $data);

    /**
     * Active client list
     *
     * @return void
     */
    public function activeClients();

    /**
     * Get active user by id
     *
     * @param int $userid
     * @return void
     */
    public function getUserById($userid);

    /**
     * Get total users
     *
     * @return void
     */
    public function totalUsers();


    /**
     * Get total users
     *
     * @return void
     */
    public function resellerTotalUsers(array $data);


    /**
     * Get total users under support manager
     *
     * @return void
     */
    public function totalUsersBySupportManager();

    /**
     * Get total users under reseller
     *
     * @return void
     */
    public function totalUsersByReseller();

    /**
     * Toal enroll client in current day
     *
     * @return void
     */
    public function todaysEnrollClientForRoot();

    /**
     * Toal enroll client in current day
     *
     * @return void
     */
    public function todaysResellerEnrollClientForRoot(array $data);

    /**
     * Toal enroll client in current month
     *
     * @return void
     */
    public function monthlyEnrollClientForRoot();

    /**
     * Toal enroll client in current month
     *
     * @return void
     */
    public function resellerMonthlyEnrollClientForRoot(array $data);
} 