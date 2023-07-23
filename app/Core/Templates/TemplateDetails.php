<?php

namespace App\Core\Templates;

use App\Core\Templates\Template;
use App\Template as TemplateModel;
use App\Datatables\DataTableClass;
use App\Http\Resources\TemplateCollection;
use App\Http\Resources\TemplateResource;

class TemplateDetails implements Template
{
    /**
     * Add Client template
     *
     * @param array $data
     * @return void
     */
    public function addTemplate(array $data)
    {
        if (!is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }

        if ($data['frmmode'] == 'ins')
        {

            new TemplateResource(TemplateModel::create([
                'template_title' => $data['template_title'],
                'template_desc' => $data['template_desc'],
                'content_file' => $data['content_file'],
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type'],
                'status' => $data['status']
            ]));

            return response()->json(['msg' => 'Template created successfully'], 200);
        } 

        if ($data['frmmode'] == 'edt')
        {
            if (!isset($data['id']) && empty($data['id']))
            {
                return back()->with('errmsg','Id Not Found');
            }
            new TemplateResource(TemplateModel::where('id', $data['id'])->update([
                'template_title' => $data['template_title'],
                'template_desc' => $data['template_desc'],
                'content_file' => $data['content_file'],
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type'],
                'status' => $data['status']
            ]));

            return response()->json(['msg' => 'Template updated successfully'], 200);
        }
    }

    /**
     * Show userwise template
     *
     * @return void
     */
    public function showTemplate($userid)
    {
        return TemplateResource::collection(

                    TemplateModel::where('user_id', $userid)
                                    ->where('status',true)
                                    ->get()
        );
    }

    public function showApprovedTemplate($tempid) {

        return TemplateModel::where('id', $tempid)
                ->where('status', true)
                ->first();
    }

    /**
     * Assign template to client
     *
     * @return void
     */
    public function assignTemplate()
    {

    }


    /**
     * Manage template status Active|Inactive
     *
     * @return void
     */
    public function manageTemplateStatus()
    {

    }

    /**
     * Delete template
     *
     * @return void
     */
    public function deleteTemplate()
    {

    }

    public function clientTemplate(array $data)
    {
        
        // DB table to use
        $table = 'templateowner';

        // Table's primary key
        $primaryKey = 'id';

        $where = " user_id = ".$data['userid']." and usertype = '".$data['usertype']."'";

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes

        
        $columns = array(
            //array( 'db' => 'id', 'dt' => 0 ),
            array( 'db' => 'id', 'dt' => 0 ),
            array( 'db' => 'template_title',  'dt' => 1 ),
            array( 'db' => 'template_desc',   'dt' => 2 ),
            array( 'db' => 'templateowner',   'dt' => 3 ),
            array( 'db' => 'usertype',   'dt' => 4 ),
            array( 'db' => 'status',   'dt' => 5 ),
            array( 'db' => 'created_at',   'dt' => 6 ),
            array( 'db' => 'updated_at',   'dt' => 7 ),
            array( 'db' => 'content_file',   'dt' => 8 ),
            array( 'db' => 'btrc_file_status',   'dt' => 9 ),
            
        );

        

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST'),
            "dsn"  => "charset=utf8"
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
    }


    public function rootTemplate()
    {
        // DB table to use
        $table = 'templateowner';

        // Table's primary key
        $primaryKey = 'id';


        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes

        
        $columns = array(
            //array( 'db' => 'id', 'dt' => 0 ),
            array( 'db' => 'id', 'dt' => 0 ),
            array( 'db' => 'template_title',  'dt' => 1 ),
            array( 'db' => 'template_desc',   'dt' => 2 ),
            array( 'db' => 'templateowner',   'dt' => 3 ),
            array( 'db' => 'usertype',   'dt' => 4 ),
            array( 'db' => 'status',   'dt' => 5 ),
            array( 'db' => 'created_at',   'dt' => 6 ),
            array( 'db' => 'updated_at',   'dt' => 7 ),
            array( 'db' => 'content_file',   'dt' => 8 ),
            array( 'db' => 'btrc_file_status',   'dt' => 9 ),
            
        );

        

        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST'),
            "dsn"  => "charset=utf8"
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * If you just want to use the basic configuration for DataTables with PHP
         * server-side, there is no need to edit below this line.
         */

        

        echo json_encode(DataTableClass::simple( $_GET, $sql_details, $table, $primaryKey,$columns));
    }
}