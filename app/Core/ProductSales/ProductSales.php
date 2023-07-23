<?php

namespace App\Core\ProductSales;

interface ProductSales
{
    /**
     * Add new invoice
     *
     * @param array $data
     * @return void
     */
    public function addInvoiceProduct(array $data);

    /**
     * Show an invoice by transection id
     *
     * @param string $trans_id
     * @return void
     */
    public function showInvoiceByTrasectionID($trans_id);

    /**
     * Show all invoices
     *
     * @return void
     */
    public function showRootClientInvoices(array $data);

    /**
     * Show all invoices
     *
     * @return void
     */
    public function showClientInvoices(array $data);

    /**
     * Show all invoices
     *
     * @return void
     */
    public function showResellerInvoices();


    /**
     * Get total sms balance by category
     *
     * @param int $userid
     * @param string $smscategory
     * @return void
     */
    public function getSmsBalanceByCategory($userid, $smscategory);

    /**
     * Get total sms balance by category
     *
     * @param int $userid
     * @param string $smscategory
     * @return void
     */
    public function getResellerSmsBalanceByCategory($userid, $smscategory);

    /**
     * Edit an invoice by transection id
     *
     * @param string $trans_id
     * @return void
     */
    public function editInvoiceById($trans_id);

    /**
     * Update an invoice
     *
     * @param array $data
     * @return void
     */
    public function updateInvoice(array $data);

    /**
     * Delete an invoice by transection id
     *
     * @param string $trans_id
     * @return void
     */
    public function deleteInvoice($trans_id);

    /**
     * Get number of today's sale
     *
     * @return void
     */
    public function totalSalesInToday();

    /**
     * Get number of today's sales of reseller
     *
     * @return void
     */
    public function resellerTotalSalesInToday(array $data);

    /**
     * Get number of today's sale
     *
     * @return void
     */
    public function totalSalesByRoot();


    /**
     * Get number of today's sale
     *
     * @return void
     */
    public function resellerTotalSalesByRoot(array $data);

    /**
     * Get total revinue of today's sale
     *
     * @return void
     */
    public function totalRevinueInTodayByRoot();

    /**
     * Get total revinue of today's sale
     *
     * @return void
     */
    public function resellerTotalRevinueInTodayByRoot(array $data);

    /**
     * Get total revinue of current month sale
     *
     * @return void
     */
    public function totalRevinueInCurrentMonthByRoot();

    /**
     * Get total revinue of current month sale
     *
     * @return void
     */
    public function resellerTotalRevinueInCurrentMonthByRoot(array $data);

    /**
     * Get total revinue of current year sale
     *
     * @return void
     */
    public function totalRevinueInCurrentYearByRoot();

    /**
     * Get total revinue of current year sale
     *
     * @return void
     */
    public function resellerTotalRevinueInCurrentYearByRoot(array $data);
}