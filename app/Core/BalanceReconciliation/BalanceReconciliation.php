<?php

namespace App\Core\BalanceReconciliation;

interface BalanceReconciliation {

    public function calculateUserBalance($userid);

}