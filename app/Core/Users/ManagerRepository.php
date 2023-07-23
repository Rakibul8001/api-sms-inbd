<?php 

namespace App\Core\Users;

use App\Manager;
use App\Datatables\DataTableClass;
use App\Core\Users\ManagerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\ManagerResource;

class ManagerRepository implements ManagerInterface
{
    /**
     * Add Reseller user
     *
     * @param array $data
     * @return void
     */
    public function addManager(array $data)
    {
        if(! is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }
        
        $check = Manager::where('email', $data['email'])->first();

        if ($check) {
            return response()->json(['errmsg' => 'This support manager already exist'], 406);
        }

        $user = new ManagerResource(Manager::create([
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
            'root_user_id' => $data['root_user_id'],
            'created_from' => $data['created_from'],
            'created_by' => $data['created_by']
        ]));

        return $user;
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

            $managerid = Auth::guard('manager')->user()->id;

            // Array of database columns which should be read and sent back to DataTables.
            // The `db` parameter represents the column name in the database, while the `dt`
            // parameter represents the DataTables column identifier. In this case simple
            // indexes

            $where = "  manager_id='$managerid'";

            
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

            

            echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
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

            $managerid = Auth::guard('manager')->user()->id;

            // Array of database columns which should be read and sent back to DataTables.
            // The `db` parameter represents the column name in the database, while the `dt`
            // parameter represents the DataTables column identifier. In this case simple
            // indexes
            
            $where = "  manager_id='$managerid'";

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

            
            echo json_encode(DataTableClass::complex( $_GET, $sql_details, $table, $primaryKey, $columns, null, $where));
    }

    public function managerUpdate($data)
    {
        //return $data;
        if(! is_array($data)) {
            return response()->json(['errmsg' => 'Data must be an array'], 406);
        }
        
        $check = Manager::where('id', $data['id'])->first();

        if (! $check) {
            return back()->withInput();//response()->json(['errmsg' => 'Root already exist'], 406);
        } 

        if ($check) {
            if (! empty($data['password'])) {
                $user = Manager::where('id',$data['id'])->update([
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
                $user = Manager::where('id',$data['id'])->update([
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

    /**
     * Get total support managers
     *
     * @return void
     */
    public function totalSupportManagers()
    {
        return Manager::count();
    }
}