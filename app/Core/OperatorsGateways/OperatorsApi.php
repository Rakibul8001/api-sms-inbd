<?php
namespace App\Core\OperatorsGateways;

interface OperatorsApi
{
    /**
     * Add Operator Gateway API
     *
     * @param array $data
     * @return void
     */
    public function addGateWayApi(array $data);

    /**
     * Update Operator Gateway API
     *
     * @param int $id
     * @return void
     */
    public function updateGatewayApi(array $data);

    /**
     * Get an api details
     *
     * @param int $id
     * @return void
     */
    public function getGatewayApi($id);

    /**
     * Get All gateway information
     *
     * @return void
     */
    public function showApiGateways();

    /**
     * Get all valid and published gateways
     *
     * @return void
     */
    public function getGateways();
}