<?php

namespace App\Core\Users;

use App\RootUser;
use App\Datatables\DataTableClass;
use Illuminate\Support\Facades\Hash;
use App\Core\Users\RootUserInterface;
use App\Http\Resources\RootUserResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RootUserRepository implements RootUserInterface
{
    /**
     * Add root user
     *
     * @param arrray $data
     * @return void
     */
    public function addRoot($data)
    {
        if(! is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }
        
        $check = RootUser::where('email', $data['email'])->first();

        if ($check) {
            return back()->withInput();//response()->json(['errmsg' => 'Root already exist'], 406);
        } 

        $user = RootUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'company' => $data['company'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'country' => $data['country'],
            'city' => $data['city'],
            'state' => $data['city'],
            'status' => $data['status'],
            'created_from' => $data['created_from'],
            'created_by' => $data['created_by']
        ]);

        return $user;


    }


    /**
     * Root user data
     *
     * @return void
     */
    public function showRootUsers()
    {
       
        // DB table to use
            $table = 'root_users';

            // Table's primary key
            $primaryKey = 'id';

            // Array of database columns which should be read and sent back to DataTables.
            // The `db` parameter represents the column name in the database, while the `dt`
            // parameter represents the DataTables column identifier. In this case simple
            // indexes
            
            $columns = array(
                array( 'db' => 'id', 'dt' => 0 ),
                array( 'db' => 'name', 'dt' => 1 ),
                array( 'db' => 'email',  'dt' => 2 ),
                array( 'db' => 'company',   'dt' => 3 ),
                array( 'db' => 'phone',   'dt' => 4 ),
                array( 'db' => 'address',   'dt' => 5 ),
                array( 'db' => 'country',   'dt' => 6 ),
                array( 'db' => 'city',   'dt' => 7 ),
                array( 'db' => 'state',   'dt' => 8 ),
                array( 'db' => 'created_from',   'dt' => 9 ),
                array( 'db' => 'created_by',   'dt' => 10 ),
                array( 'db' => 'created_at',   'dt' => 11 ),
                array( 'db' => 'status',   'dt' => 12 ),
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
     * Show support manager data
     *
     * @return void
     */
    public function showManagers()
    {
       
        // DB table to use
            $table = 'managers';

            // Table's primary key
            $primaryKey = 'id';

            // Array of database columns which should be read and sent back to DataTables.
            // The `db` parameter represents the column name in the database, while the `dt`
            // parameter represents the DataTables column identifier. In this case simple
            // indexes
            
            $columns = array(
                array( 'db' => 'id', 'dt' => 0 ),
                array( 'db' => 'name', 'dt' => 1 ),
                array( 'db' => 'email',  'dt' => 2 ),
                array( 'db' => 'company',   'dt' => 3 ),
                array( 'db' => 'phone',   'dt' => 4 ),
                array( 'db' => 'address',   'dt' => 5 ),
                array( 'db' => 'country',   'dt' => 6 ),
                array( 'db' => 'city',   'dt' => 7 ),
                array( 'db' => 'state',   'dt' => 8 ),
                array( 'db' => 'created_from',   'dt' => 9 ),
                array( 'db' => 'created_by',   'dt' => 10 ),
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
     * Show reseller data
     *
     * @return void
     */
    public function showResellers()
    {
       
        // DB table to use
            $table = 'resellers';

            // Table's primary key
            $primaryKey = 'id';

            // Array of database columns which should be read and sent back to DataTables.
            // The `db` parameter represents the column name in the database, while the `dt`
            // parameter represents the DataTables column identifier. In this case simple
            // indexes
            
            $columns = array(
                array( 'db' => 'id', 'dt' => 0 ),
                array( 'db' => 'name', 'dt' => 1 ),
                array( 'db' => 'email',  'dt' => 2 ),
                array( 'db' => 'company',   'dt' => 3 ),
                array( 'db' => 'phone',   'dt' => 4 ),
                array( 'db' => 'address',   'dt' => 5 ),
                array( 'db' => 'country',   'dt' => 6 ),
                array( 'db' => 'city',   'dt' => 7 ),
                array( 'db' => 'state',   'dt' => 8 ),
                array( 'db' => 'created_from',   'dt' => 9 ),
                array( 'db' => 'created_by',   'dt' => 10 ),
                array( 'db' => 'created_at',   'dt' => 11 ),
                array( 'db' => 'status',   'dt' => 12 ),
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
     * Show clients data
     *
     * @return void
     */
    public function showClients()
    {
       
        // DB table to use
            $table = 'users';

            // Table's primary key
            $primaryKey = 'id';

            // Array of database columns which should be read and sent back to DataTables.
            // The `db` parameter represents the column name in the database, while the `dt`
            // parameter represents the DataTables column identifier. In this case simple
            // indexes
            
            $columns = array(
                array( 'db' => 'id', 'dt' => 0 ),
                array( 'db' => 'name', 'dt' => 1 ),
                array( 'db' => 'email',  'dt' => 2 ),
                array( 'db' => 'company',   'dt' => 3 ),
                array( 'db' => 'phone',   'dt' => 4 ),
                array( 'db' => 'address',   'dt' => 5 ),
                array( 'db' => 'country',   'dt' => 6 ),
                array( 'db' => 'city',   'dt' => 7 ),
                array( 'db' => 'state',   'dt' => 8 ),
                array( 'db' => 'created_from',   'dt' => 9 ),
                array( 'db' => 'created_by',   'dt' => 10 ),
                array( 'db' => 'status',   'dt' => 11 ),
                array( 'db' => 'created_at',   'dt' => 12 ),
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


    public function rootUserEdit(RootUser $user)
    {
        return new RootUserResource(RootUser::where('id', $user->id)->firstOrFail());
    }

    /**
     * Root user update
     *
     * @param array $data
     * @return void
     */
    public function rootUserUpdate($data)
    {
        //return $data;
        if(! is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }
        
        $check = RootUser::where('id', $data['id'])->first();

        if (! $check) {
            return back()->withInput();//response()->json(['errmsg' => 'Root already exist'], 406);
        } 

        if ($check) {
            if (! empty($data['password'])) {
                $user = RootUser::where('id',$data['id'])->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'company' => $data['company'],
                    'address' => $data['address'],
                    'country' => $data['country'],
                    'city' => $data['city'],
                    'state' => $data['city'],
                    'status' => $data['status']
                ]);
            } else {
                $user = RootUser::where('id',$data['id'])->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'company' => $data['company'],
                    'address' => $data['address'],
                    'country' => $data['country'],
                    'city' => $data['city'],
                    'state' => $data['city'],
                    'status' => $data['status']
                ]);
            }
        }

        return $user;
    }

}