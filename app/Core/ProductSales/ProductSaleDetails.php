<?php

namespace App\Core\ProductSales;

use App\ProductSale;
use App\Datatables\DataTableClass;
use App\Core\ProductSales\ProductSales;
use App\Http\Resources\ProductSaleResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductSaleDetails implements ProductSales
{

    /**
     * Add new invoice
     *
     * @param array $data
     * @return void
     */
    public function addInvoiceProduct(array $data)
    {
        if (! is_array($data))
        {
            return response()->json(['errmsg' => 'Data must be an array'],406);
        }

        return new ProductSaleResource(ProductSale::create([
            'user_id' => $data['user_id'],
            'user_type' => $data['user_type'],
            'transection_id' => $data['transection_id'],
            'sms_category' => $data['sms_category'],
            'qty' => $data['qty'],
            'qty_return' => $data['qty_return'],
            'rate' => $data['rate'],
            'price' => $data['price'],
            'validity_period' => $data['validity_period'],
            'invoice_vat' => $data['invoice_vat'],
            'vat_amount' => $data['vat_amount'],
            'invoice_date' => $data['invoice_date'],
            'invoice_owner_type' => $data['invoice_owner_type'],
            'invoice_owner_id' => $data['invoice_owner_id'],
        ]));
    }

    /**
     * Show an invoice by transection id
     *
     * @param string $trans_id
     * @return void
     */
    public function showInvoiceByTrasectionID($trans_id)
    {
        
    }


    /**
     * Show all invoices
     *
     * @return void
     */
    public function showRootClientInvoices($data)
    {
        // DB table to use
        $table = 'sms_sale_to_client_summery';

        // Table's primary key
        $primaryKey = 'saleid';

        if ($data['invoice_owner_type'] === 'root') {
            $where = " invoice_owner_type = '".$data['invoice_owner_type']."'";
        } else {
            $where = " invoice_owner_type = '".$data['invoice_owner_type']."' and invoice_owner_id = '".$data['invoice_owner_id']."'";
        }
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'saleid', 'dt' => 0 ),
            array( 'db' => 'clientname', 'dt' => 1 ),
            array( 'db' => 'clientemail',  'dt' => 2 ),
            array( 'db' => 'phone',   'dt' => 3 ),
            array( 'db' => 'transection_id',   'dt' => 4 ),
            array( 'db' => 'qty',   'dt' => 5 ),
            array( 'db' => 'rate',   'dt' => 6 ),
            array( 'db' => 'price',   'dt' => 7 ),
            array( 'db' => 'validity_period',   'dt' => 8 ),
            array( 'db' => 'invoice_vat',   'dt' => 9 ),
            array( 'db' => 'vat_amount',   'dt' => 10 ),
            array( 'db' => 'invoice_date',   'dt' => 11 ),
            array( 'db' => 'invoice_owner_type',   'dt' => 12 ),
            array( 'db' => 'sms_category',   'dt' => 13 ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
    }

    /**
     * Show all invoices
     *
     * @return void
     */
    public function showResellerInvoices()
    {
        // DB table to use
        $table = 'sms_sale_to_reseller_summery';

        // Table's primary key
        $primaryKey = 'saleid';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'saleid', 'dt' => 0 ),
            array( 'db' => 'clientname', 'dt' => 1 ),
            array( 'db' => 'clientemail',  'dt' => 2 ),
            array( 'db' => 'phone',   'dt' => 3 ),
            array( 'db' => 'transection_id',   'dt' => 4 ),
            array( 'db' => 'qty',   'dt' => 5 ),
            array( 'db' => 'rate',   'dt' => 6 ),
            array( 'db' => 'price',   'dt' => 7 ),
            array( 'db' => 'validity_period',   'dt' => 8 ),
            array( 'db' => 'invoice_vat',   'dt' => 9 ),
            array( 'db' => 'vat_amount',   'dt' => 10 ),
            array( 'db' => 'invoice_date',   'dt' => 11 ),
            array( 'db' => 'invoice_owner_type',   'dt' => 12 ),
            array( 'db' => 'sms_category',   'dt' => 13 ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::simple( $_GET, $sql_details, $table, $primaryKey,$columns));
    }


    /**
     * Show client invoices
     *
     * @return void
     */
    public function showClientInvoices($data)
    {
        // DB table to use
        $table = 'sms_sale_to_client_summery';

        // Table's primary key
        $primaryKey = 'saleid';

        //$where = " invoice_owner_type = '".$data['invoice_owner_type']."' and invoice_owner_id = '".$data['invoice_owner_id']."'";
        $where = " user_id = '".$data['userid']."'";

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'saleid', 'dt' => 0 ),
            array( 'db' => 'clientname', 'dt' => 1 ),
            array( 'db' => 'clientemail',  'dt' => 2 ),
            array( 'db' => 'phone',   'dt' => 3 ),
            array( 'db' => 'transection_id',   'dt' => 4 ),
            array( 'db' => 'qty',   'dt' => 5 ),
            array( 'db' => 'rate',   'dt' => 6 ),
            array( 'db' => 'price',   'dt' => 7 ),
            array( 'db' => 'validity_period',   'dt' => 8 ),
            array( 'db' => 'invoice_vat',   'dt' => 9 ),
            array( 'db' => 'vat_amount',   'dt' => 10 ),
            array( 'db' => 'invoice_date',   'dt' => 11 ),
            array( 'db' => 'invoice_owner_type',   'dt' => 12 ),
            array( 'db' => 'sms_category',   'dt' => 13 ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
    }


    /**
     * Show client invoices
     *
     * @return void
     */
    public function showMyResellerInvoices($data)
    {
        // DB table to use
        $table = 'sms_sale_to_reseller_summery';

        // Table's primary key
        $primaryKey = 'saleid';

        //$where = " invoice_owner_type = '".$data['invoice_owner_type']."' and invoice_owner_id = '".$data['invoice_owner_id']."'";
        $where = " user_id = '".$data['userid']."'";

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'saleid', 'dt' => 0 ),
            array( 'db' => 'clientname', 'dt' => 1 ),
            array( 'db' => 'clientemail',  'dt' => 2 ),
            array( 'db' => 'phone',   'dt' => 3 ),
            array( 'db' => 'transection_id',   'dt' => 4 ),
            array( 'db' => 'qty',   'dt' => 5 ),
            array( 'db' => 'rate',   'dt' => 6 ),
            array( 'db' => 'price',   'dt' => 7 ),
            array( 'db' => 'validity_period',   'dt' => 8 ),
            array( 'db' => 'invoice_vat',   'dt' => 9 ),
            array( 'db' => 'vat_amount',   'dt' => 10 ),
            array( 'db' => 'invoice_date',   'dt' => 11 ),
            array( 'db' => 'invoice_owner_type',   'dt' => 12 ),
            array( 'db' => 'sms_category',   'dt' => 13 ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
    }


    /**
     * Get total sms balance by category
     *
     * @param int $userid
     * @param string $smscategory
     * @return void
     */
    public function getSmsBalanceByCategory($userid, $smscategory)
    {
        $saleqty = ProductSale::where('user_id', $userid)->where('sms_category', $smscategory)->sum('qty');

        $rtnqty = ProductSale::where('user_id', $userid)->where('sms_category', $smscategory)->sum('qty_return');

        return $saleqty-$rtnqty;
    }

    /**
     * Get total sms balance by category
     *
     * @param int $userid
     * @param string $smscategory
     * @return void
     */
    public function getResellerSmsBalanceByCategory($userid, $smscategory)
    {
        $saleqty = ProductSale::where('user_id', $userid)->where('user_type','reseller')->where('sms_category', $smscategory)->sum('qty');
        
        $rtnqty = ProductSale::where('user_id', $userid)->where('user_type','reseller')->where('sms_category', $smscategory)->sum('qty_return');

        return $saleqty-$rtnqty;
    }



    /**
     * Edit an invoice by transection id
     *
     * @param string $trans_id
     * @return void
     */
    public function editInvoiceById($trans_id)
    {
        
    }


    /**
     * Update an invoice
     *
     * @param array $data
     * @return void
     */
    public function updateInvoice(array $data)
    {
        
    }

    /**
     * Delete an invoice by transection id
     *
     * @param string $trans_id
     * @return void
     */
    public function deleteInvoice($trans_id)
    {
        
    }

    /**
     * Get number of today's sale
     *
     * @return void
     */
    public function totalSalesInToday()
    {
      
        $sales = ProductSale::where('invoice_owner_type','root')
                            ->where('qty_return',0)
                            //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                            ->whereDate('created_at', Carbon::today())
                            ->groupBy('transection_id')->get();

        return count($sales);
    }


    /**
     * Get number of today's sales of reseller
     *
     * @return void
     */
    public function resellerTotalSalesInToday($data)
    {
      
        $sales = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return',0)
                            ->whereDate('created_at', Carbon::today())
                            ->groupBy('transection_id')->get();

        return count($sales);
    }

    /**
     * Get number of today's sale
     *
     * @return void
     */
    public function totalSalesByRoot()
    {
        $sales = ProductSale::where('invoice_owner_type','root')
                            //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                            ->where('qty_return',0)
                            ->groupBy('transection_id')->get();

        return count($sales);
    }


    /**
     * Get number of today's sale
     *
     * @return void
     */
    public function resellerTotalSalesByRoot($data)
    {
        $sales = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return',0)
                            ->groupBy('transection_id')->get();

        return count($sales);
    }

    /**
     * Get total revinue of today's sale
     *
     * @return void
     */
    public function totalRevinueInTodayByRoot()
    {
      
        $revinue = ProductSale::where('invoice_owner_type','root')
                            //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                            ->where('qty_return',0)
                            ->whereDate('created_at', Carbon::today())
                            ->sum('price');


        $revinuertn = ProductSale::where('invoice_owner_type','root')
                        //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                        ->where('qty_return','>',0)
                        ->whereDate('created_at', Carbon::today())
                        ->sum('price');

        return $revinue-$revinuertn;
    }

    /**
     * Get total revinue of today's sale
     *
     * @return void
     */
    public function resellerTotalRevinueInTodayByRoot($data)
    {
      
        $revinue = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return',0)
                            ->whereDate('created_at', Carbon::today())
                            ->sum('price');

        $revinuertn = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return','>',0)
                            ->whereDate('created_at', Carbon::today())
                            ->sum('price');

        return $revinue-$revinuertn;
    }

    /**
     * Get total revinue of current month sale
     *
     * @return void
     */
    public function totalRevinueInCurrentMonthByRoot()
    {
        $revinue = ProductSale::where('invoice_owner_type','root')
                            //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                            ->where('qty_return',0)
                            ->whereMonth('created_at', Carbon::now()->format("m"))
                            ->sum('price');

        $revinuertn = ProductSale::where('invoice_owner_type','root')
                            //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                            ->where('qty_return','>',0)
                            ->whereMonth('created_at', Carbon::now()->format("m"))
                            ->sum('price');

        return $revinue-$revinuertn;
    }

    /**
     * Get total revinue of current month sale
     *
     * @return void
     */
    public function resellerTotalRevinueInCurrentMonthByRoot($data)
    {
        $revinue = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return',0)
                            ->whereMonth('created_at', Carbon::now()->format("m"))
                            ->sum('price');

        $revinuertn = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return','>',0)
                            ->whereMonth('created_at', Carbon::now()->format("m"))
                            ->sum('price');

        return $revinue-$revinuertn;
    }

    /**
     * Get total revinue of current year sale
     *
     * @return void
     */
    public function totalRevinueInCurrentYearByRoot()
    {
        $revinue = ProductSale::where('invoice_owner_type','root')
                            //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                            ->where('qty_return',0)
                            ->whereYear('created_at', Carbon::now()->format("Y"))
                            ->sum('price');

        $revinuertn = ProductSale::where('invoice_owner_type','root')
                            //->where('invoice_owner_id', Auth::guard('root')->user()->id)
                            ->where('qty_return','>',0)
                            ->whereYear('created_at', Carbon::now()->format("Y"))
                            ->sum('price');

        return $revinue-$revinuertn;
    }

    /**
     * Get total revinue of current year sale
     *
     * @return void
     */
    public function resellerTotalRevinueInCurrentYearByRoot($data)
    {
        $revinue = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return',0)
                            ->whereYear('created_at', Carbon::now()->format("Y"))
                            ->sum('price');

        $revinuertn = ProductSale::where('invoice_owner_type','reseller')
                            ->where('invoice_owner_id', $data['invoice_owner_id'])
                            ->where('qty_return','>',0)
                            ->whereYear('created_at', Carbon::now()->format("Y"))
                            ->sum('price');

        return $revinue-$revinuertn;
    }
    
}