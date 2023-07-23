<?php

namespace App\Core\Templates;

interface Template
{
    /**
     * Add Client template
     *
     * @param array $data
     * @return void
     */
    public function addTemplate(array $data);

    /**
     * Show userwise template
     *
     * @return void
     */
    public function showTemplate($userid);

    /**
     * Assign template to client
     *
     * @return void
     */
    public function assignTemplate();


    public function showApprovedTemplate($tempid);


    /**
     * Manage template status Active|Inactive
     *
     * @return void
     */
    public function manageTemplateStatus();

    /**
     * Delete template
     *
     * @return void
     */
    public function deleteTemplate();


    public function clientTemplate(array $data);

    public function rootTemplate();
}