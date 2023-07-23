<?php

namespace App\Core\OperatorsGateways;

interface Operators
{
    /**
     * Add new operator
     *
     * @param array $data
     * @return void
     */
    public function addOperator(array $data);


    /**
     * Update sms operator
     *
     * @param int $id
     * @return void
     */
    public function updateOperator(array $data);

    /**
     * Show operators
     *
     * @return void
     */
    public function showOperators();

    /**
     * Delete an operator
     *
     * @param int $id
     * @return void
     */
    public function deleteOperator($id);

    /**
     * Get all operators
     *
     * @return void
     */
    public function getOperators();
}