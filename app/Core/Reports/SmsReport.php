<?php

namespace App\Core\Reports;

interface SmsReport
{
    public function successDlr(array $data);
}