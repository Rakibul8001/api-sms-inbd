<?php

namespace App\Core\SmsAdminSettings;

interface SmsAdminSettings
{
    /**
     * Persis default settings to the view
     *
     * @return void
     */
    public function showSmsAdminSettings();

    /**
     * Update settings config values at run time
     *
     * @param array $data
     * @return void
     */
    public function smsAdminSettingsUpdate(array $data);
}