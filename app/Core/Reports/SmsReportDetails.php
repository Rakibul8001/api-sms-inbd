<?php

namespace App\Core\Reports;

use App\Core\Reports\SmsReport;
use App\Datatables\DataTableClass;

class SmsReportDetails implements SmsReport
{
    public function successDlr(array $data){
        // DB table to use
        $table = 'success_dlr';

        // Table's primary key
        $primaryKey = 'smscountid';

        $where = " user_id = '".$data['userid']."'  and DATE(created_at) between '".$data['fromdate']."' and '".$data['todate']."'";

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        
        $columns = array(
            array( 'db' => 'smscountid', 'dt' => 0 ),
            array( 'db' => 'campaing_name', 'dt' => 1 ),
            array( 'db' => 'sms_count',  'dt' => 2 ),
            array( 'db' => 'created_at',   'dt' => 3 ),
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
}