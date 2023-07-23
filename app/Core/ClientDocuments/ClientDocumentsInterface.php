<?php

namespace App\Core\ClientDocuments;

use Illuminate\Http\Request;

interface ClientDocumentsInterface
{

    /**
     * Add Nid Copy
     *
     * @param Request $request
     * @return void
     */
    public function addNid(Request $request);

    /**
     * Add Application Copy
     *
     * @param Request $request
     * @return void
     */
    public function addApplication(Request $request);

    /**
     * Add Custom PP Photo
     *
     * @param Request $request
     * @return void
     */
    public function addCustomPhoto(Request $request);

    /**
     * Add Trade Licence Copy
     *
     * @param Request $request
     * @return void
     */
    public function addTradeLicence(Request $request);


    public function showUserDocuments(Request $request);
}